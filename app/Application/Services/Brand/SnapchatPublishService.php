<?php

namespace App\Application\Services\Brand;

use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SnapchatPublishService
{
    public function __construct(private readonly SocialConnectService $connect)
    {
    }

    /** @return array{id: string, url: string|null} */
    public function publish(ContentItem $contentItem): array
    {
        [$account, $token, $profileId] = $this->resolveCredentials($contentItem);

        try {
            $token = $this->connect->ensureFreshToken($account);
        } catch (\Throwable) {
            // Use stored token if refresh fails.
        }

        $asset = $this->resolveMediaAsset($contentItem);
        $publicUrl = $this->resolvePublicMediaUrl($contentItem);

        if (! $asset && ! $publicUrl) {
            throw new RuntimeException('No image or video found. Upload a vertical image or .mp4 (5–60 sec) for Snapchat Story.');
        }

        $contents = $asset
            ? $this->readAssetContents($asset)
            : $this->downloadUrl($publicUrl);

        $mime = $asset?->mime_type
            ?? ($this->looksLikeVideo($contents) ? 'video/mp4' : 'image/jpeg');

        $mediaId = $this->createMedia($profileId, $token, $mime);
        $this->multipartUpload($profileId, $token, $mediaId, $contents, $mime);
        $storyId = $this->postStory($profileId, $token, $mediaId);

        $handle = ltrim((string) ($account->account_handle ?: ''), '@');

        return [
            'id' => $storyId,
            'url' => $handle !== '' ? "https://www.snapchat.com/add/{$handle}" : null,
        ];
    }

    /** @return array{0: SocialAccount, 1: string, 2: string} */
    private function resolveCredentials(ContentItem $contentItem): array
    {
        $account = SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'snapchat')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderByDesc('connected_at')
            ->first();

        if (! $account?->oauthToken?->access_token) {
            throw new RuntimeException('No Snapchat account is connected. Open Social accounts → Snapchat → Connect.');
        }

        $profileId = (string) $account->external_id;

        if ($profileId === '' || str_starts_with($profileId, 'demo-')) {
            throw new RuntimeException('Snapchat profile is not linked. Reconnect your Snapchat Public Profile.');
        }

        return [$account, $account->oauthToken->access_token, $profileId];
    }

    private function createMedia(string $profileId, string $token, string $mime): string
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://businessapi.snapchat.com/v1/public_profiles/{$profileId}/media", [
                'media_type' => str_starts_with($mime, 'video/') ? 'VIDEO' : 'IMAGE',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not create Snapchat media object.'));
        }

        $mediaId = (string) (
            $response->json('media.id')
            ?? $response->json('media_id')
            ?? data_get($response->json(), 'media.0.id')
            ?? ''
        );

        if ($mediaId === '') {
            throw new RuntimeException('Snapchat did not return a media ID.');
        }

        return $mediaId;
    }

    private function multipartUpload(string $profileId, string $token, string $mediaId, string $contents, string $mime): void
    {
        $response = Http::withToken($token)
            ->attach('file', $contents, str_starts_with($mime, 'video/') ? 'story.mp4' : 'story.jpg')
            ->post("https://businessapi.snapchat.com/us/v1/public_profiles/{$profileId}/media/{$mediaId}/multipart-upload");

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not upload media to Snapchat.'));
        }

        $finalizePath = (string) ($response->json('finalize_path') ?? '');

        if ($finalizePath !== '') {
            $finalize = Http::withToken($token)
                ->acceptJson()
                ->post('https://businessapi.snapchat.com'.$finalizePath);

            if (! $finalize->successful()) {
                throw new RuntimeException($this->apiError($finalize, 'Could not finalize Snapchat media upload.'));
            }
        }
    }

    private function postStory(string $profileId, string $token, string $mediaId): string
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://businessapi.snapchat.com/v1/public_profiles/{$profileId}/stories", [
                'media_id' => $mediaId,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish Snapchat Story.'));
        }

        return (string) (
            $response->json('story.id')
            ?? $response->json('stories.0.story.id')
            ?? $mediaId
        );
    }

    private function resolveMediaAsset(ContentItem $item): ?BrandAsset
    {
        $postType = data_get($item->metadata, 'post_type', 'image');
        $isVideo = $postType === 'reel' || $item->content_type === 'reel_script';

        $assetId = data_get($item->metadata, $isVideo ? 'video_asset_id' : 'image_asset_id')
            ?? data_get($item->metadata, 'asset_id');

        if ($assetId) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where('id', $assetId)
                ->first();
        }

        if ($isVideo) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where(function ($q) {
                    $q->where('file_type', 'like', 'video%')
                        ->orWhere('mime_type', 'like', 'video/%');
                })
                ->latest()
                ->first();
        }

        return BrandAsset::query()
            ->where('brand_id', $item->brand_id)
            ->where(function ($q) {
                $q->where('file_type', 'like', 'image%')
                    ->orWhere('mime_type', 'like', 'image/%');
            })
            ->latest()
            ->first();
    }

    private function resolvePublicMediaUrl(ContentItem $item): ?string
    {
        $postType = data_get($item->metadata, 'post_type', 'image');
        $isVideo = $postType === 'reel' || $item->content_type === 'reel_script';
        $key = $isVideo ? 'video_url' : 'thumbnail_url';
        $url = data_get($item->metadata, $key);

        return is_string($url) && $url !== '' ? $url : null;
    }

    private function readAssetContents(BrandAsset $asset): string
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Media file is missing from storage.');
        }

        return Storage::disk($disk)->get($asset->file_path);
    }

    private function downloadUrl(string $url): string
    {
        $response = Http::timeout(120)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Could not download media for Snapchat upload.');
        }

        return $response->body();
    }

    private function looksLikeVideo(string $contents): bool
    {
        return str_starts_with($contents, "\x00\x00\x00") || str_contains(substr($contents, 0, 12), 'ftyp');
    }

    private function apiError(\Illuminate\Http\Client\Response $response, string $fallback): string
    {
        $message = (string) (
            $response->json('request_status')
            ?? $response->json('debug_message')
            ?? $response->json('display_message')
            ?? $response->json('error.message')
            ?? ''
        );

        if ($response->status() === 401 || $response->status() === 403) {
            return $fallback.' Snapchat session expired or app not allowlisted. Social Accounts → Snapchat → Reconnect.';
        }

        return $message !== '' ? $fallback.' '.$message : $fallback;
    }
}
