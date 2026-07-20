<?php

namespace App\Application\Services\Brand;

use App\Infrastructure\Facebook\FacebookGraph;
use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FacebookPublishService
{
    public function __construct(private readonly SocialConnectService $connect)
    {
    }

    public function publish(ContentItem $contentItem): array
    {
        $postType = data_get($contentItem->metadata, 'post_type', 'image');

        if ($postType === 'reel' || $contentItem->content_type === 'reel_script') {
            $postType = 'reel';
        }

        [$pageId, $pageToken, $account] = $this->resolveFacebookCredentials($contentItem);
        $message = trim($contentItem->body ?? '');

        if ($message === '') {
            $message = trim($contentItem->title ?? '');
        }

        if ($postType === 'reel') {
            if ($message === '') {
                throw new RuntimeException('Add caption text before publishing this Reel.');
            }

            return $this->publishReel($pageId, $pageToken, $message, $contentItem, $account);
        }

        $isCarousel = $postType === 'carousel' || $contentItem->content_type === 'carousel';

        if ($isCarousel) {
            if ($message === '') {
                throw new RuntimeException('Post content is empty. Add caption text before publishing.');
            }

            return $this->publishCarousel($pageId, $pageToken, $message, $contentItem, $account);
        }

        if ($message === '') {
            throw new RuntimeException('Post content is empty. Add caption text before publishing.');
        }

        $asset = $this->resolveImageAsset($contentItem);

        if ($asset) {
            return $this->publishPhoto($pageId, $pageToken, $message, $asset, $account);
        }

        $publicImageUrl = $this->resolvePublicImageUrl($contentItem);

        if ($publicImageUrl) {
            return $this->publishPhotoByUrl($pageId, $pageToken, $message, $publicImageUrl, $account);
        }

        return $this->publishText($pageId, $pageToken, $message, $account);
    }

    public function publicImageUrlForExternalFetch(ContentItem $contentItem, BrandAsset $asset): string
    {
        if ($this->isVideoAsset($asset)) {
            throw new RuntimeException('Expected an image file but found a video. Use a video upload flow for Reels.');
        }

        [$pageId, $pageToken] = $this->resolveFacebookPageForBrand($contentItem->brand_id);
        $photoId = $this->uploadUnpublishedPhotoFromAsset($pageId, $pageToken, $asset);

        return $this->resolvePhotoSourceUrl($photoId, $pageToken);
    }

    public function publicVideoUrlForExternalFetch(ContentItem $contentItem, BrandAsset $asset): string
    {
        if (! $this->isVideoAsset($asset)) {
            throw new RuntimeException('Selected file is not a video. Upload an MP4 video for Instagram Reels.');
        }

        [$pageId, $pageToken, $account] = $this->resolveFacebookPageAccountForBrand($contentItem->brand_id);

        try {
            $pageToken = $this->connect->refreshFacebookPageToken($account);
        } catch (\Throwable) {
            // Use the stored page token if refresh fails.
        }

        return $this->uploadUnpublishedVideoAndGetSourceUrl($pageId, $pageToken, $asset);
    }

    /** @return array{0: string, 1: string} */
    private function resolveFacebookPageForBrand(int $brandId): array
    {
        [$pageId, $pageToken] = array_slice($this->resolveFacebookPageAccountForBrand($brandId), 0, 2);

        return [$pageId, $pageToken];
    }

    /** @return array{0: string, 1: string, 2: SocialAccount} */
    private function resolveFacebookPageAccountForBrand(int $brandId): array
    {
        $account = SocialAccount::query()
            ->where('brand_id', $brandId)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderByDesc('connected_at')
            ->first();

        if (! $account) {
            throw new RuntimeException($this->explainMissingFacebookAccount($brandId));
        }

        if (! $account->oauthToken?->access_token) {
            throw new RuntimeException(
                'Facebook connection is incomplete for this brand. Go to Social accounts → disconnect Facebook → connect again, then publish.'
            );
        }

        $pageToken = $this->connect->ensureFacebookPageToken($account);

        return [(string) $account->external_id, $pageToken, $account];
    }

    private function uploadUnpublishedVideoAndGetSourceUrl(string $pageId, string $pageToken, BrandAsset $asset): string
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Reel video file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);

        $response = FacebookGraph::http()->attach('source', $contents, $asset->file_name)
            ->post("https://graph.facebook.com/v21.0/{$pageId}/videos", [
                'published' => 'false',
                'access_token' => $pageToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not upload Reel video to Facebook for Instagram.'));
        }

        $videoId = (string) ($response->json('id') ?: '');

        if ($videoId === '') {
            throw new RuntimeException('Facebook did not return a video ID for Reel upload.');
        }

        return $this->waitForVideoSourceUrl($videoId, $pageToken);
    }

    private function waitForVideoSourceUrl(string $videoId, string $pageToken, int $maxAttempts = 90): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$videoId}", [
                'fields' => 'status,source',
                'access_token' => $pageToken,
            ]);

            if ($response->successful()) {
                $source = $response->json('source');

                if (is_string($source) && $source !== '') {
                    return $source;
                }

                $status = (string) ($response->json('status.video_status') ?? $response->json('status') ?? '');

                if (in_array($status, ['error', 'failed'], true)) {
                    throw new RuntimeException('Facebook video processing failed for Instagram Reel.');
                }
            }

            sleep(2);
        }

        throw new RuntimeException('Video processing timed out. Try again in a minute.');
    }

    private function isVideoAsset(BrandAsset $asset): bool
    {
        return str_starts_with((string) $asset->file_type, 'video')
            || str_starts_with((string) ($asset->mime_type ?? ''), 'video/');
    }

    private function resolvePhotoSourceUrl(string $photoId, string $pageToken): string
    {
        $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$photoId}", [
            'fields' => 'images',
            'access_token' => $pageToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not fetch public image URL from Facebook.'));
        }

        $images = $response->json('images', []);

        if (! is_array($images) || $images === []) {
            throw new RuntimeException('Facebook did not return a public image URL for Instagram.');
        }

        usort($images, fn ($a, $b) => ($b['width'] ?? 0) <=> ($a['width'] ?? 0));

        $source = (string) ($images[0]['source'] ?? '');

        if ($source === '') {
            throw new RuntimeException('Facebook did not return a public image URL for Instagram.');
        }

        return $source;
    }

    /** @return array{0: string, 1: string, 2: SocialAccount} */
    private function resolveFacebookCredentials(ContentItem $contentItem): array
    {
        $preferredId = data_get($contentItem->metadata, 'social_account_id');

        $accounts = SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderByDesc('connected_at')
            ->get();

        if ($accounts->isEmpty()) {
            throw new RuntimeException($this->explainMissingFacebookAccount($contentItem->brand_id));
        }

        if ($preferredId && strtolower((string) $contentItem->platform) === 'facebook') {
            $preferred = $accounts->firstWhere('id', (int) $preferredId);

            if ($preferred) {
                $accounts = collect([$preferred])
                    ->merge($accounts->reject(fn (SocialAccount $account) => $account->id === $preferred->id));
            }
        }

        $lastError = null;

        foreach ($accounts as $account) {
            if (! $account->oauthToken?->access_token) {
                continue;
            }

            try {
                $pageToken = $this->connect->ensureFacebookPageToken($account);

                return [$account->external_id, $pageToken, $account];
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new RuntimeException(
            'No Facebook Page is connected for this brand. Open Social accounts → Connect account → Facebook.'
        );
    }

    /** @return array{id: string, url: string|null} */
    private function publishReel(string $pageId, string $pageToken, string $message, ContentItem $item, SocialAccount $account): array
    {
        $asset = $this->resolveVideoAsset($item);
        $publicVideoUrl = $this->resolvePublicVideoUrl($item);

        if (! $asset && ! $publicVideoUrl) {
            throw new RuntimeException('No video file found for this Reel. Upload a .mp4 video in Post planning first.');
        }

        $pageToken = $this->connect->refreshFacebookPageToken($account);

        $init = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/video_reels", [
            'upload_phase' => 'start',
            'access_token' => $pageToken,
        ]);

        if (! $init->successful() && $this->isPageTokenError($init)) {
            $pageToken = $this->connect->refreshFacebookPageToken($account->fresh(['oauthToken']));
            $init = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/video_reels", [
                'upload_phase' => 'start',
                'access_token' => $pageToken,
            ]);
        }

        if (! $init->successful()) {
            throw new RuntimeException($this->apiError($init, 'Could not start Facebook Reel upload.'));
        }

        $videoId = (string) $init->json('video_id', '');

        if ($videoId === '') {
            throw new RuntimeException('Facebook did not return a video ID for Reel upload.');
        }

        if ($asset) {
            $this->uploadReelFile($videoId, $pageToken, $asset);
        } else {
            $this->uploadReelFromUrl($videoId, $pageToken, $publicVideoUrl);
        }

        $publish = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/video_reels", [
            'upload_phase' => 'finish',
            'video_state' => 'PUBLISHED',
            'video_id' => $videoId,
            'description' => $message,
            'access_token' => $pageToken,
        ]);

        if (! $publish->successful()) {
            throw new RuntimeException($this->apiError($publish, 'Could not publish Reel to Facebook.'));
        }

        $postId = (string) ($publish->json('post_id') ?: $videoId);

        return [
            'id' => $videoId,
            'url' => $this->buildPostUrl($postId),
        ];
    }

    private function uploadReelFile(string $videoId, string $pageToken, BrandAsset $asset): void
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Reel video file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);
        $fileSize = strlen($contents);

        $response = FacebookGraph::http()->withHeaders([
            'Authorization' => 'OAuth '.$pageToken,
            'offset' => '0',
            'file_size' => (string) $fileSize,
        ])->withBody($contents, 'application/octet-stream')
            ->post("https://rupload.facebook.com/video-upload/v21.0/{$videoId}");

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($this->apiError($response, 'Could not upload Reel video to Facebook.'));
        }
    }

    private function uploadReelFromUrl(string $videoId, string $pageToken, string $videoUrl): void
    {
        $response = FacebookGraph::http()->withHeaders([
            'Authorization' => 'OAuth '.$pageToken,
            'file_url' => $videoUrl,
        ])->post("https://rupload.facebook.com/video-upload/v21.0/{$videoId}");

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($this->apiError($response, 'Could not upload Reel video from URL to Facebook.'));
        }
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

        if ($manualType === 'reel' && filled($manualKey) && ctype_digit((string) $manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where('id', (int) $manualKey)
                ->first();
        }

        if ($manualType === 'reel' && filled($manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where('metadata->content_group', $manualKey)
                ->where(function ($query) {
                    $query->where('file_type', 'like', 'video/%')
                        ->orWhere('mime_type', 'like', 'video/%');
                })
                ->first();
        }

        return null;
    }

    private function resolvePublicVideoUrl(ContentItem $item): ?string
    {
        $videoUrl = data_get($item->metadata, 'video_url');

        if (is_string($videoUrl) && $this->isPublicHttpUrl($videoUrl) && ! str_contains($videoUrl, '/assets/')) {
            return $videoUrl;
        }

        return null;
    }

    /** @return array{id: string, url: string|null} */
    private function publishCarousel(string $pageId, string $pageToken, string $message, ContentItem $item, SocialAccount $account): array
    {
        $assets = $this->resolveCarouselImageAssets($item);
        $publicUrls = $this->resolveCarouselPublicUrls($item);

        if ($assets === [] && $publicUrls === []) {
            throw new RuntimeException('No carousel images found. Add images in Post planning first.');
        }

        $photoIds = [];

        foreach ($assets as $asset) {
            $photoIds[] = $this->uploadUnpublishedPhotoFromAsset($pageId, $pageToken, $asset);
        }

        foreach ($publicUrls as $url) {
            $photoIds[] = $this->uploadUnpublishedPhotoFromUrl($pageId, $pageToken, $url);
        }

        if ($photoIds === []) {
            throw new RuntimeException('Could not upload carousel images to Facebook.');
        }

        return $this->publishFeedWithAttachedMedia($pageId, $pageToken, $message, $photoIds, $account);
    }

    /** @param  list<string>  $photoIds */
    private function publishFeedWithAttachedMedia(string $pageId, string $pageToken, string $message, array $photoIds, SocialAccount $account): array
    {
        $payload = [
            'message' => $message,
            'access_token' => $pageToken,
        ];

        foreach (array_values($photoIds) as $index => $photoId) {
            $payload["attached_media[{$index}]"] = json_encode(['media_fbid' => $photoId]);
        }

        $response = FacebookGraph::http()->asForm()->post("https://graph.facebook.com/v21.0/{$pageId}/feed", $payload);

        $refreshedToken = $this->refreshPageTokenAfterFailure($account, $response);

        if ($refreshedToken) {
            $payload['access_token'] = $refreshedToken;
            $response = FacebookGraph::http()->asForm()->post("https://graph.facebook.com/v21.0/{$pageId}/feed", $payload);
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish carousel post to Facebook.'));
        }

        $postId = (string) $response->json('id', '');

        return [
            'id' => $postId,
            'url' => $this->buildPostUrl($postId),
        ];
    }

    private function uploadUnpublishedPhotoFromAsset(string $pageId, string $pageToken, BrandAsset $asset): string
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Carousel image file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);

        $response = FacebookGraph::http()->attach('source', $contents, $asset->file_name)
            ->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                'published' => 'false',
                'access_token' => $pageToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not upload carousel image to Facebook.'));
        }

        $photoId = (string) ($response->json('id') ?: '');

        if ($photoId === '') {
            throw new RuntimeException('Facebook did not return a photo ID for carousel upload.');
        }

        return $photoId;
    }

    private function uploadUnpublishedPhotoFromUrl(string $pageId, string $pageToken, string $imageUrl): string
    {
        $response = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
            'url' => $imageUrl,
            'published' => 'false',
            'access_token' => $pageToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not upload carousel image URL to Facebook.'));
        }

        $photoId = (string) ($response->json('id') ?: '');

        if ($photoId === '') {
            throw new RuntimeException('Facebook did not return a photo ID for carousel URL upload.');
        }

        return $photoId;
    }

    /** @return list<BrandAsset> */
    private function resolveCarouselImageAssets(ContentItem $item): array
    {
        $brandId = $item->brand_id;
        $assets = [];
        $seenIds = [];

        foreach (data_get($item->metadata, 'carousel_images', []) as $url) {
            $asset = $this->assetFromUrl($brandId, (string) $url);

            if ($asset && ! in_array($asset->id, $seenIds, true)) {
                $assets[] = $asset;
                $seenIds[] = $asset->id;
            }
        }

        if ($assets !== []) {
            return $assets;
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if ($manualType === 'carousel' && filled($manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('metadata->carousel_group', $manualKey)
                ->orderBy('metadata->slot')
                ->get()
                ->all();
        }

        return [];
    }

    /** @return list<string> */
    private function resolveCarouselPublicUrls(ContentItem $item): array
    {
        $urls = [];

        foreach (data_get($item->metadata, 'carousel_images', []) as $url) {
            $url = (string) $url;

            if ($this->assetFromUrl($item->brand_id, $url)) {
                continue;
            }

            if ($this->isPublicHttpUrl($url) && ! str_contains($url, '/assets/')) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /** @return array{id: string, url: string|null} */
    private function publishText(string $pageId, string $pageToken, string $message, SocialAccount $account): array
    {
        $response = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
            'message' => $message,
            'access_token' => $pageToken,
        ]);

        $refreshedToken = $this->refreshPageTokenAfterFailure($account, $response);

        if ($refreshedToken) {
            $response = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
                'message' => $message,
                'access_token' => $refreshedToken,
            ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish text post to Facebook.'));
        }

        $postId = (string) $response->json('id', '');

        return [
            'id' => $postId,
            'url' => $this->buildPostUrl($postId),
        ];
    }

    /** @return array{id: string, url: string|null} */
    private function publishPhotoByUrl(string $pageId, string $pageToken, string $message, string $imageUrl, SocialAccount $account): array
    {
        $response = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
            'url' => $imageUrl,
            'message' => $message,
            'access_token' => $pageToken,
        ]);

        $refreshedToken = $this->refreshPageTokenAfterFailure($account, $response);

        if ($refreshedToken) {
            $response = FacebookGraph::http()->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                'url' => $imageUrl,
                'message' => $message,
                'access_token' => $refreshedToken,
            ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish photo post to Facebook.'));
        }

        $postId = (string) ($response->json('post_id') ?: $response->json('id') ?: '');

        return [
            'id' => $postId,
            'url' => $this->buildPostUrl($postId),
        ];
    }

    /** @return array{id: string, url: string|null} */
    private function publishPhoto(string $pageId, string $pageToken, string $message, BrandAsset $asset, SocialAccount $account): array
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Post image file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);

        $response = FacebookGraph::http()->attach('source', $contents, $asset->file_name)
            ->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                'message' => $message,
                'access_token' => $pageToken,
            ]);

        $refreshedToken = $this->refreshPageTokenAfterFailure($account, $response);

        if ($refreshedToken) {
            $response = FacebookGraph::http()->attach('source', $contents, $asset->file_name)
                ->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                    'message' => $message,
                    'access_token' => $refreshedToken,
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish photo post to Facebook.'));
        }

        $postId = (string) ($response->json('post_id') ?: $response->json('id') ?: '');

        return [
            'id' => $postId,
            'url' => $this->buildPostUrl($postId),
        ];
    }

    private function resolveImageAsset(ContentItem $item): ?BrandAsset
    {
        $brandId = $item->brand_id;
        $postType = data_get($item->metadata, 'post_type', 'image');

        if ($postType === 'carousel') {
            foreach (data_get($item->metadata, 'carousel_images', []) as $url) {
                $asset = $this->assetFromUrl($brandId, (string) $url);

                if ($asset) {
                    return $asset;
                }
            }
        }

        $thumb = data_get($item->metadata, 'thumbnail_url');

        if ($thumb) {
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

    private function resolvePublicImageUrl(ContentItem $item): ?string
    {
        $candidates = [];

        if ($thumb = data_get($item->metadata, 'thumbnail_url')) {
            $candidates[] = (string) $thumb;
        }

        foreach (data_get($item->metadata, 'carousel_images', []) as $url) {
            $candidates[] = (string) $url;
        }

        foreach ($candidates as $url) {
            if ($this->isPublicHttpUrl($url) && ! str_contains($url, '/assets/')) {
                return $url;
            }
        }

        return null;
    }

    private function isPublicHttpUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $localHosts = ['localhost', '127.0.0.1', '[::1]'];

        return ! in_array(strtolower($host), $localHosts, true);
    }

    private function buildPostUrl(string $postId): ?string
    {
        if ($postId === '') {
            return null;
        }

        return 'https://www.facebook.com/'.$postId;
    }

    private function explainMissingFacebookAccount(int $brandId): string
    {
        $disconnected = SocialAccount::onlyTrashed()
            ->where('brand_id', $brandId)
            ->where('platform', 'facebook')
            ->exists();

        if ($disconnected) {
            return 'Facebook was disconnected for this brand. Open Social accounts and connect Facebook again, then publish.';
        }

        return 'No Facebook Page is connected for this brand. Open Social accounts → Connect account → Facebook (use the same brand you are publishing from).';
    }

    private function apiError(\Illuminate\Http\Client\Response $response, string $fallback): string
    {
        $message = (string) ($response->json('error.message') ?? '');
        $code = (int) $response->json('error.code', 0);
        $subcode = (int) $response->json('error.error_subcode', 0);

        $sessionDead = $code === 190 && (
            $subcode === 460
            || str_contains($message, 'session has been invalidated')
            || str_contains($message, 'changed their password')
            || str_contains($message, 'session is invalid')
            || str_contains($message, 'Error validating access token')
        );

        if ($sessionDead) {
            return $fallback.' Facebook login session expire / invalidate ho gaya (password change ya Meta security). Social Accounts → Viral Post / Facebook Page pe Reconnect dabao, permissions allow karo, phir publish try karo.';
        }

        if ($code === 190 || str_contains($message, 'impersonating a user\'s page')) {
            return $fallback.' Facebook Page permissions are missing or outdated. Open Social Accounts once to refresh the connection, then publish again.'.($message !== '' ? ' Meta: '.$message : '');
        }

        if ($code === 4 || str_contains($message, 'Application request limit reached')) {
            return $fallback.' Facebook API limit hit. 30–60 minute wait karke phir publish karo.';
        }

        return $message !== '' ? $fallback.' '.$message : $fallback;
    }

    private function isPageTokenError(\Illuminate\Http\Client\Response $response): bool
    {
        $code = (int) $response->json('error.code', 0);
        $message = (string) $response->json('error.message', '');

        return $code === 190
            || $code === 200
            || str_contains($message, 'impersonating a user\'s page');
    }

    private function refreshPageTokenAfterFailure(SocialAccount $account, \Illuminate\Http\Client\Response $response): ?string
    {
        if (! $this->isPageTokenError($response)) {
            return null;
        }

        try {
            return $this->connect->ensureFacebookPageToken($account->fresh(['oauthToken']));
        } catch (\Throwable) {
            return null;
        }
    }
}
