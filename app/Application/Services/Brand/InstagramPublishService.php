<?php

namespace App\Application\Services\Brand;

use App\Infrastructure\Facebook\FacebookGraph;
use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InstagramPublishService
{
    public function __construct(
        private readonly SocialConnectService $connect,
        private readonly PublishMediaUrlGenerator $mediaUrls,
        private readonly FacebookPublishService $facebook,
    ) {
    }

    public function publish(ContentItem $contentItem): array
    {
        $postType = data_get($contentItem->metadata, 'post_type', 'image');

        if ($postType === 'reel' || $contentItem->content_type === 'reel_script') {
            return $this->publishReel($contentItem);
        }

        if ($postType === 'carousel' || $contentItem->content_type === 'carousel') {
            return $this->publishCarousel($contentItem);
        }

        return $this->publishImage($contentItem);
    }

    /** @return array{id: string, url: string|null} */
    private function publishImage(ContentItem $item): array
    {
        [$igUserId, $token] = $this->resolveInstagramCredentials($item);
        $caption = $this->resolveCaption($item);
        $imageUrl = $this->resolvePublicImageUrl($item);

        if ($imageUrl === null) {
            throw new RuntimeException('No image found for this Instagram post. Add an image in Post planning first.');
        }

        $containerId = $this->createMediaContainer($igUserId, $token, [
            'image_url' => $imageUrl,
            'caption' => $caption,
        ]);

        return $this->publishContainer($igUserId, $token, $containerId);
    }

    /** @return array{id: string, url: string|null} */
    private function publishCarousel(ContentItem $item): array
    {
        [$igUserId, $token] = $this->resolveInstagramCredentials($item);
        $caption = $this->resolveCaption($item);
        $imageUrls = $this->resolveCarouselPublicUrls($item);

        if ($imageUrls === []) {
            throw new RuntimeException('No carousel images found. Add images in Post planning first.');
        }

        if (count($imageUrls) > 10) {
            $imageUrls = array_slice($imageUrls, 0, 10);
        }

        $childIds = [];

        foreach ($imageUrls as $imageUrl) {
            $childIds[] = $this->createMediaContainer($igUserId, $token, [
                'image_url' => $imageUrl,
                'is_carousel_item' => 'true',
            ]);
        }

        $containerId = $this->createMediaContainer($igUserId, $token, [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childIds),
            'caption' => $caption,
        ]);

        return $this->publishContainer($igUserId, $token, $containerId);
    }

    /** @return array{id: string, url: string|null} */
    private function publishReel(ContentItem $item): array
    {
        [$igUserId, $token] = $this->resolveInstagramCredentials($item);
        $caption = $this->resolveCaption($item);
        $asset = $this->resolveVideoAsset($item);
        $publicVideoUrl = $this->firstPublicVideoUrl($item);

        if (! $asset && ! $publicVideoUrl) {
            throw new RuntimeException('No video found for this Reel. Upload an MP4 video in Post planning first.');
        }

        if ($asset && ! $this->isVideoAsset($asset)) {
            throw new RuntimeException('Selected media is not a video file. Upload an MP4 video for Instagram Reels.');
        }

        $containerId = $this->createResumableReelContainer($igUserId, $token, $caption);

        if ($asset instanceof BrandAsset) {
            $this->uploadReelBinary($containerId, $token, $asset);
        } else {
            $this->uploadReelFromPublicUrl($containerId, $token, (string) $publicVideoUrl);
        }

        $this->waitForMediaContainer($containerId, $token, 120);

        return $this->publishContainer($igUserId, $token, $containerId);
    }

    private function createResumableReelContainer(string $igUserId, string $token, string $caption): string
    {
        $response = FacebookGraph::http()->asForm()->post("https://graph.facebook.com/v21.0/{$igUserId}/media", [
            'upload_type' => 'resumable',
            'media_type' => 'REELS',
            'caption' => $caption,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not start Instagram Reel upload.'));
        }

        $containerId = (string) ($response->json('id') ?: '');

        if ($containerId === '') {
            throw new RuntimeException('Instagram did not return a Reel container ID.');
        }

        return $containerId;
    }

    private function uploadReelBinary(string $containerId, string $token, BrandAsset $asset): void
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Reel video file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);
        $fileSize = strlen($contents);

        $response = FacebookGraph::http()->withHeaders([
            'Authorization' => 'OAuth '.$token,
            'offset' => '0',
            'file_size' => (string) $fileSize,
        ])->withBody($contents, 'application/octet-stream')
            ->post("https://rupload.facebook.com/ig-api-upload/v21.0/{$containerId}");

        if (! $response->successful() || ! $response->json('success')) {
            $message = $response->json('debug_info.message') ?? $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Could not upload Reel video to Instagram. '.((string) $message));
        }
    }

    private function uploadReelFromPublicUrl(string $containerId, string $token, string $videoUrl): void
    {
        $response = FacebookGraph::http()->withHeaders([
            'Authorization' => 'OAuth '.$token,
            'file_url' => $videoUrl,
        ])->post("https://rupload.facebook.com/ig-api-upload/v21.0/{$containerId}");

        if (! $response->successful() || ! $response->json('success')) {
            $message = $response->json('debug_info.message') ?? $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Could not upload Reel video from URL. '.((string) $message));
        }
    }

    private function firstPublicVideoUrl(ContentItem $item): ?string
    {
        $videoUrl = data_get($item->metadata, 'video_url');

        if (is_string($videoUrl) && $this->mediaUrls->isPublicHttpUrl($videoUrl) && ! str_contains($videoUrl, '/assets/')) {
            return $videoUrl;
        }

        return null;
    }

    /** @param  array<string, string>  $params */
    private function createMediaContainer(string $igUserId, string $token, array $params, int $maxWaitAttempts = 40): string
    {
        $response = FacebookGraph::http()->asForm()->post("https://graph.facebook.com/v21.0/{$igUserId}/media", [
            ...$params,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not prepare Instagram media.'));
        }

        $containerId = (string) $response->json('id', '');

        if ($containerId === '') {
            throw new RuntimeException('Instagram did not return a media container ID.');
        }

        $this->waitForMediaContainer($containerId, $token, $maxWaitAttempts);

        return $containerId;
    }

    /** @return array{id: string, url: string|null} */
    private function publishContainer(string $igUserId, string $token, string $containerId): array
    {
        $response = FacebookGraph::http()->asForm()->post("https://graph.facebook.com/v21.0/{$igUserId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish to Instagram.'));
        }

        $mediaId = (string) $response->json('id', '');

        return [
            'id' => $mediaId,
            'url' => $this->resolvePermalink($mediaId, $token),
        ];
    }

    private function waitForMediaContainer(string $containerId, string $token, int $maxAttempts = 40): void
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$containerId}", [
                'fields' => 'status_code,status',
                'access_token' => $token,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException($this->apiError($response, 'Could not verify Instagram media status.'));
            }

            $statusCode = (string) $response->json('status_code', '');

            if ($statusCode === 'FINISHED') {
                return;
            }

            if ($statusCode === 'ERROR') {
                $status = (string) ($response->json('status') ?: 'Unknown error');

                if (str_contains($status, '2207076')) {
                    throw new RuntimeException(
                        'Instagram could not download the video. The app now uploads Reels directly — please try publishing again. If it still fails, use an MP4 file (H.264, under 90 seconds).'
                    );
                }

                throw new RuntimeException('Instagram media processing failed: '.$status);
            }

            usleep(500_000);
        }

        throw new RuntimeException('Instagram media processing timed out. Please try again.');
    }

    private function resolvePermalink(string $mediaId, string $token): ?string
    {
        if ($mediaId === '') {
            return null;
        }

        $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$mediaId}", [
            'fields' => 'permalink',
            'access_token' => $token,
        ]);

        $permalink = $response->json('permalink');

        return is_string($permalink) && $permalink !== '' ? $permalink : null;
    }

    /** @return array{0: string, 1: string, 2: SocialAccount} */
    private function resolveInstagramCredentials(ContentItem $contentItem): array
    {
        $accountId = data_get($contentItem->metadata, 'social_account_id');

        $query = SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'instagram')
            ->where('status', 'active')
            ->with('oauthToken');

        if ($accountId) {
            $query->where('id', (int) $accountId);
        }

        $account = $query->orderByDesc('connected_at')->first();

        if (! $account) {
            throw new RuntimeException($this->explainMissingInstagramAccount($contentItem->brand_id));
        }

        if (! $account->oauthToken?->access_token) {
            throw new RuntimeException(
                'Instagram connection is incomplete. Go to Social accounts → connect Instagram again, then publish.'
            );
        }

        $token = $account->oauthToken->access_token;

        $facebookAccount = SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->first();

        if ($facebookAccount) {
            try {
                $token = $this->connect->refreshFacebookPageToken($facebookAccount);
                $account->oauthToken->update(['access_token' => $token]);
            } catch (\Throwable) {
                // Keep the stored page token if refresh fails.
            }
        }

        return [(string) $account->external_id, $token, $account];
    }

    private function resolveCaption(ContentItem $item): string
    {
        $caption = trim($item->body ?? '');

        if ($caption === '') {
            $caption = trim($item->title ?? '');
        }

        if ($caption === '') {
            throw new RuntimeException('Add caption text before publishing to Instagram.');
        }

        return $caption;
    }

    private function resolvePublicImageUrl(ContentItem $item): ?string
    {
        $public = $this->firstPublicUrl([
            data_get($item->metadata, 'thumbnail_url'),
            ...(array) data_get($item->metadata, 'carousel_images', []),
        ]);

        if ($public) {
            return $public;
        }

        $asset = $this->resolveImageAsset($item);

        return $asset ? $this->publishableUrlForAsset($item, $asset) : null;
    }

    /** @return list<string> */
    private function resolveCarouselPublicUrls(ContentItem $item): array
    {
        $urls = [];
        $seen = [];

        foreach (data_get($item->metadata, 'carousel_images', []) as $url) {
            $url = (string) $url;

            if ($this->mediaUrls->isPublicHttpUrl($url) && ! str_contains($url, '/assets/')) {
                if (! in_array($url, $seen, true)) {
                    $urls[] = $url;
                    $seen[] = $url;
                }

                continue;
            }

            $asset = $this->assetFromUrl($item->brand_id, $url);

            if ($asset) {
                $publicUrl = $this->publishableUrlForAsset($item, $asset);

                if (! in_array($publicUrl, $seen, true)) {
                    $urls[] = $publicUrl;
                    $seen[] = $publicUrl;
                }
            }
        }

        if ($urls !== []) {
            return $urls;
        }

        foreach ($this->resolveCarouselImageAssets($item) as $asset) {
            $publicUrl = $this->publishableUrlForAsset($item, $asset);

            if (! in_array($publicUrl, $seen, true)) {
                $urls[] = $publicUrl;
                $seen[] = $publicUrl;
            }
        }

        return $urls;
    }

    private function resolvePublicVideoUrl(ContentItem $item): ?string
    {
        $videoUrl = data_get($item->metadata, 'video_url');

        if (is_string($videoUrl) && $this->mediaUrls->isPublicHttpUrl($videoUrl) && ! str_contains($videoUrl, '/assets/')) {
            return $videoUrl;
        }

        $asset = $this->resolveVideoAsset($item);

        return $asset ? $this->publishableVideoUrlForAsset($item, $asset) : null;
    }

    private function publishableVideoUrlForAsset(ContentItem $item, BrandAsset $asset): string
    {
        if (! $this->isVideoAsset($asset)) {
            throw new RuntimeException('Selected media is not a video file. Upload an MP4 video for Instagram Reels.');
        }

        try {
            return $this->mediaUrls->signedUrlForAsset($asset);
        } catch (RuntimeException) {
            return $this->facebook->publicVideoUrlForExternalFetch($item, $asset);
        }
    }

    private function publishableUrlForAsset(ContentItem $item, BrandAsset $asset): string
    {
        if ($this->isVideoAsset($asset)) {
            return $this->publishableVideoUrlForAsset($item, $asset);
        }

        try {
            return $this->mediaUrls->signedUrlForAsset($asset);
        } catch (RuntimeException) {
            return $this->facebook->publicImageUrlForExternalFetch($item, $asset);
        }
    }

    private function isVideoAsset(BrandAsset $asset): bool
    {
        return str_starts_with((string) $asset->file_type, 'video')
            || str_starts_with((string) ($asset->mime_type ?? ''), 'video/');
    }

    /** @param  list<mixed>  $candidates */
    private function firstPublicUrl(array $candidates): ?string
    {
        foreach ($candidates as $url) {
            $url = (string) $url;

            if ($this->mediaUrls->isPublicHttpUrl($url) && ! str_contains($url, '/assets/')) {
                return $url;
            }
        }

        return null;
    }

    private function resolveImageAsset(ContentItem $item): ?BrandAsset
    {
        $brandId = $item->brand_id;

        if ($thumb = data_get($item->metadata, 'thumbnail_url')) {
            $asset = $this->assetFromUrl($brandId, (string) $thumb);

            if ($asset) {
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

        if ($manualType === 'caption' && filled($manualKey)) {
            $query = BrandAsset::query()->where('brand_id', $brandId);
            $assets = ctype_digit((string) $manualKey)
                ? $query->where('id', (int) $manualKey)->get()
                : $query->where('metadata->content_group', $manualKey)->get();

            return $assets->first(fn (BrandAsset $asset) => data_get($asset->metadata, 'role') === 'image'
                || str_starts_with((string) $asset->file_type, 'image'));
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

    private function resolveVideoAsset(ContentItem $item): ?BrandAsset
    {
        $videoUrl = data_get($item->metadata, 'video_url');

        if ($videoUrl) {
            $asset = $this->assetFromUrl($item->brand_id, (string) $videoUrl);

            if ($asset) {
                return $asset;
            }
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if ($manualType === 'reel' && filled($manualKey)) {
            if (ctype_digit((string) $manualKey)) {
                return BrandAsset::query()
                    ->where('brand_id', $item->brand_id)
                    ->where('id', (int) $manualKey)
                    ->first();
            }

            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where(function ($query) use ($manualKey) {
                    $query->where('metadata->content_group', $manualKey)
                        ->orWhere('metadata->carousel_group', $manualKey);
                })
                ->where(function ($query) {
                    $query->where('file_type', 'like', 'video%')
                        ->orWhere('mime_type', 'like', 'video/%');
                })
                ->first();
        }

        return null;
    }

    /** @return list<BrandAsset> */
    private function resolveCarouselImageAssets(ContentItem $item): array
    {
        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if ($manualType !== 'carousel' || ! filled($manualKey)) {
            return [];
        }

        return BrandAsset::query()
            ->where('brand_id', $item->brand_id)
            ->where('metadata->carousel_group', $manualKey)
            ->orderBy('metadata->slot')
            ->get()
            ->all();
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

    private function explainMissingInstagramAccount(int $brandId): string
    {
        $disconnected = SocialAccount::onlyTrashed()
            ->where('brand_id', $brandId)
            ->where('platform', 'instagram')
            ->exists();

        if ($disconnected) {
            return 'Instagram was disconnected for this brand. Open Social accounts and connect Instagram again, then publish.';
        }

        return 'No Instagram Business account is connected. Open Social accounts → Connect account → Instagram (link your Facebook Page first).';
    }

    private function apiError(\Illuminate\Http\Client\Response $response, string $fallback): string
    {
        $message = $response->json('error.message');
        $code = (int) $response->json('error.code', 0);

        if ($code === 190 || ($message && str_contains($message, 'OAuthException'))) {
            return $fallback.' Instagram permissions may be missing or expired. Reconnect Facebook and Instagram in Social accounts.'.($message ? ' Meta says: '.$message : '');
        }

        if ($message && str_contains($message, 'instagram_content_publish')) {
            return $fallback.' Add instagram_content_publish permission in your Meta app and reconnect Facebook/Instagram.';
        }

        return $message ? $fallback.' '.$message : $fallback;
    }
}
