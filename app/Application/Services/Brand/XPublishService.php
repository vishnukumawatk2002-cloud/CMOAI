<?php

namespace App\Application\Services\Brand;

use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class XPublishService
{
    public function __construct(private readonly SocialConnectService $connect)
    {
    }

    /** @return array{id: string, url: string|null} */
    public function publish(ContentItem $contentItem): array
    {
        $postType = data_get($contentItem->metadata, 'post_type', 'image');

        if ($postType === 'reel' || $contentItem->content_type === 'reel_script') {
            throw new RuntimeException('Video Reels are not supported on X yet. Publish as an image post or text-only post.');
        }

        [$account, $token] = $this->resolveCredentials($contentItem);
        $text = $this->resolveText($contentItem);

        try {
            $token = $this->connect->ensureFreshToken($account);
        } catch (\Throwable) {
            // Use stored token if refresh fails.
        }

        $mediaIds = [];
        $warning = null;
        $asset = $this->resolveImageAsset($contentItem);

        if ($asset) {
            if ($this->isVideoAsset($asset)) {
                throw new RuntimeException('Video posts are not supported on X yet. Use an image or text-only post.');
            }

            try {
                $mediaIds[] = $this->uploadImage($token, $asset);
            } catch (RuntimeException $e) {
                if ($this->isCreditsOrPlanLimitError($e->getMessage())) {
                    $warning = 'Image was not included because your X Developer account has no media upload credits. The post was published as text only. Add credits or upgrade your X API plan at developer.x.com to attach images.';
                } else {
                    throw $e;
                }
            }
        }

        $payload = ['text' => $this->truncateTweet($text)];

        if ($mediaIds !== []) {
            $payload['media'] = ['media_ids' => $mediaIds];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://api.x.com/2/tweets', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish to X.'));
        }

        $tweetId = (string) ($response->json('data.id') ?: '');
        $handle = ltrim((string) ($account->account_handle ?: ''), '@');

        return [
            'id' => $tweetId,
            'url' => $tweetId !== '' && $handle !== ''
                ? "https://x.com/{$handle}/status/{$tweetId}"
                : ($tweetId !== '' ? "https://x.com/i/web/status/{$tweetId}" : null),
            'warning' => $warning,
        ];
    }

    private function isCreditsOrPlanLimitError(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'does not have any credits')
            || str_contains($message, 'usage-capped')
            || str_contains($message, 'usage cap')
            || str_contains($message, 'client-not-enrolled')
            || str_contains($message, 'appropriate level of api access');
    }

    /** @return array{0: SocialAccount, 1: string} */
    private function resolveCredentials(ContentItem $contentItem): array
    {
        $accountId = data_get($contentItem->metadata, 'social_account_id');

        $query = SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'x')
            ->where('status', 'active')
            ->with('oauthToken');

        if ($accountId) {
            $query->where('id', (int) $accountId);
        }

        $account = $query->orderByDesc('connected_at')->first();

        if (! $account) {
            throw new RuntimeException('No X account is connected. Open Social accounts → Connect X, then publish.');
        }

        if (! $account->oauthToken?->access_token || str_starts_with((string) $account->external_id, 'demo-')) {
            throw new RuntimeException('X connection is incomplete. Disconnect the demo account and connect via OAuth in Social accounts.');
        }

        return [$account, $account->oauthToken->access_token];
    }

    private function resolveText(ContentItem $item): string
    {
        $text = trim($item->body ?? '');

        if ($text === '') {
            $text = trim($item->title ?? '');
        }

        if ($text === '') {
            throw new RuntimeException('Add post text before publishing to X.');
        }

        return $text;
    }

    private function truncateTweet(string $text): string
    {
        if (mb_strlen($text) <= 280) {
            return $text;
        }

        return mb_substr($text, 0, 277).'...';
    }

    private function uploadImage(string $token, BrandAsset $asset): string
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Image file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);
        $mime = $this->resolveImageMimeType($asset);
        $filename = $asset->file_name ?: 'image.jpg';
        $fileSize = strlen($contents);

        if ($fileSize > 5 * 1024 * 1024) {
            return $this->uploadImageChunked($token, $contents, $mime);
        }

        return $this->uploadImageOneShot($token, $contents, $filename, $mime);
    }

    private function uploadImageOneShot(string $token, string $contents, string $filename, string $mime): string
    {
        $response = Http::withToken($token)
            ->attach('media', $contents, $filename, ['Content-Type' => $mime])
            ->post('https://api.x.com/2/media/upload', [
                'media_category' => 'tweet_image',
                'media_type' => $mime,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->mediaUploadError($response));
        }

        return $this->extractMediaId($response);
    }

    private function uploadImageChunked(string $token, string $contents, string $mime): string
    {
        $totalBytes = strlen($contents);

        $initResponse = Http::withToken($token)
            ->acceptJson()
            ->post('https://api.x.com/2/media/upload/initialize', [
                'media_type' => $mime,
                'total_bytes' => $totalBytes,
                'media_category' => 'tweet_image',
            ]);

        if (! $initResponse->successful()) {
            throw new RuntimeException($this->mediaUploadError($initResponse));
        }

        $mediaId = $this->extractMediaId($initResponse);
        $chunkSize = 4 * 1024 * 1024;
        $segmentIndex = 0;

        for ($offset = 0; $offset < $totalBytes; $offset += $chunkSize) {
            $chunk = substr($contents, $offset, $chunkSize);

            $appendResponse = Http::withToken($token)
                ->attach('media', $chunk, 'chunk.bin')
                ->post("https://api.x.com/2/media/upload/{$mediaId}/append", [
                    'segment_index' => (string) $segmentIndex,
                ]);

            if (! $appendResponse->successful()) {
                throw new RuntimeException($this->mediaUploadError($appendResponse));
            }

            $segmentIndex++;
        }

        $finalizeResponse = Http::withToken($token)
            ->acceptJson()
            ->post("https://api.x.com/2/media/upload/{$mediaId}/finalize");

        if (! $finalizeResponse->successful()) {
            throw new RuntimeException($this->mediaUploadError($finalizeResponse));
        }

        return $mediaId;
    }

    private function extractMediaId(\Illuminate\Http\Client\Response $response): string
    {
        $mediaId = (string) ($response->json('data.id') ?: $response->json('media_id_string') ?: '');

        if ($mediaId === '') {
            throw new RuntimeException('X did not return a media ID for the uploaded image.');
        }

        return $mediaId;
    }

    private function resolveImageMimeType(BrandAsset $asset): string
    {
        $mime = (string) ($asset->mime_type ?? '');

        if ($mime !== '' && str_starts_with($mime, 'image/')) {
            return $mime;
        }

        $extension = strtolower(pathinfo((string) $asset->file_name, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'tif', 'tiff' => 'image/tiff',
            default => 'image/jpeg',
        };
    }

    private function mediaUploadError(\Illuminate\Http\Client\Response $response): string
    {
        $message = $this->apiError($response, 'Could not upload image to X.');

        if ($response->status() === 403 || str_contains(strtolower($message), 'media.write')) {
            $message .= ' Disconnect X in Social accounts and connect again to grant media.write permission.';
        }

        return $message;
    }

    private function resolveImageAsset(ContentItem $item): ?BrandAsset
    {
        $brandId = $item->brand_id;
        $postType = data_get($item->metadata, 'post_type', 'image');

        if ($postType === 'carousel') {
            foreach (data_get($item->metadata, 'carousel_images', []) as $url) {
                $asset = $this->assetFromUrl($brandId, (string) $url);

                if ($asset && ! $this->isVideoAsset($asset)) {
                    return $asset;
                }
            }
        }

        $thumb = data_get($item->metadata, 'thumbnail_url');

        if ($thumb) {
            $asset = $this->assetFromUrl($brandId, (string) $thumb);

            if ($asset && ! $this->isVideoAsset($asset)) {
                return $asset;
            }
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if (! filled($manualKey)) {
            return null;
        }

        if ($manualType === 'image' && ctype_digit((string) $manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('id', (int) $manualKey)
                ->first();
        }

        if ($manualType === 'carousel' && filled($manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('metadata->carousel_group', $manualKey)
                ->orderBy('metadata->slot')
                ->first();
        }

        return null;
    }

    private function assetFromUrl(int $brandId, string $url): ?BrandAsset
    {
        if (! preg_match('#/assets/(\d+)(?:/|\?|$)#', $url, $matches)) {
            return null;
        }

        return BrandAsset::query()
            ->where('brand_id', $brandId)
            ->where('id', (int) $matches[1])
            ->first();
    }

    private function isVideoAsset(BrandAsset $asset): bool
    {
        return str_starts_with((string) $asset->file_type, 'video')
            || str_starts_with((string) ($asset->mime_type ?? ''), 'video/');
    }

    private function apiError(\Illuminate\Http\Client\Response $response, string $fallback): string
    {
        $detail = $response->json('detail')
            ?? $response->json('title')
            ?? $response->json('error.message')
            ?? $response->json('errors.0.message')
            ?? $response->json('errors.0.detail');

        if (is_string($detail) && str_contains(strtolower($detail), 'oauth')) {
            return $fallback.' Reconnect X in Social accounts. X says: '.$detail;
        }

        return is_string($detail) && $detail !== '' ? $fallback.' '.$detail : $fallback;
    }
}
