<?php

namespace App\Application\Services\Brand;

use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LinkedInPublishService
{
    public function __construct(private readonly SocialConnectService $connect)
    {
    }

    /** @return array{id: string, url: string|null} */
    public function publish(ContentItem $contentItem): array
    {
        $postType = data_get($contentItem->metadata, 'post_type', 'image');

        if ($postType === 'reel' || $contentItem->content_type === 'reel_script') {
            throw new RuntimeException('Video Reels are not supported on LinkedIn yet. Publish as an image post or text-only post.');
        }

        [$account, $token] = $this->resolveCredentials($contentItem);
        $text = $this->resolveText($contentItem);

        try {
            $token = $this->connect->ensureFreshToken($account);
        } catch (\Throwable) {
            // Use stored token if refresh fails.
        }

        $authorUrn = 'urn:li:person:'.$account->external_id;
        $payload = [
            'author' => $authorUrn,
            'commentary' => $text,
            'visibility' => 'PUBLIC',
            'distribution' => [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        $asset = $this->resolveImageAsset($contentItem);

        if ($asset) {
            if ($this->isVideoAsset($asset)) {
                throw new RuntimeException('Video posts are not supported on LinkedIn yet. Use an image or text-only post.');
            }

            $imageUrn = $this->uploadImage($token, $authorUrn, $asset);
            $payload['content'] = [
                'media' => [
                    'id' => $imageUrn,
                ],
            ];
        }

        $response = Http::withToken($token)
            ->withHeaders($this->linkedInHeaders())
            ->acceptJson()
            ->post('https://api.linkedin.com/rest/posts', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->apiError($response, 'Could not publish to LinkedIn.'));
        }

        $postUrn = (string) ($response->header('x-restli-id') ?: $response->json('id') ?: '');
        $postId = $this->extractPostId($postUrn);

        return [
            'id' => $postId ?: $postUrn,
            'url' => $postId ? "https://www.linkedin.com/feed/update/{$postUrn}" : null,
        ];
    }

    /** @return array{0: SocialAccount, 1: string} */
    private function resolveCredentials(ContentItem $contentItem): array
    {
        $accountId = data_get($contentItem->metadata, 'social_account_id');

        $query = SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'linkedin')
            ->where('status', 'active')
            ->with('oauthToken');

        if ($accountId) {
            $query->where('id', (int) $accountId);
        }

        $account = $query->orderByDesc('connected_at')->first();

        if (! $account) {
            throw new RuntimeException('No LinkedIn account is connected. Open Social accounts → Connect LinkedIn, then publish.');
        }

        if (! $account->oauthToken?->access_token || str_starts_with((string) $account->external_id, 'demo-')) {
            throw new RuntimeException('LinkedIn connection is incomplete. Disconnect the demo account and connect via OAuth in Social accounts.');
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
            throw new RuntimeException('Add post text before publishing to LinkedIn.');
        }

        return $text;
    }

    private function uploadImage(string $token, string $ownerUrn, BrandAsset $asset): string
    {
        $initResponse = Http::withToken($token)
            ->withHeaders($this->linkedInHeaders())
            ->acceptJson()
            ->post('https://api.linkedin.com/rest/images?action=initializeUpload', [
                'initializeUploadRequest' => [
                    'owner' => $ownerUrn,
                ],
            ]);

        if (! $initResponse->successful()) {
            throw new RuntimeException($this->apiError($initResponse, 'Could not prepare LinkedIn image upload.'));
        }

        $uploadUrl = (string) ($initResponse->json('value.uploadUrl') ?: '');
        $imageUrn = (string) ($initResponse->json('value.image') ?: '');

        if ($uploadUrl === '' || $imageUrn === '') {
            throw new RuntimeException('LinkedIn did not return image upload details.');
        }

        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Image file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);
        $mime = $asset->mime_type ?: 'image/jpeg';

        $uploadResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => $mime,
        ])->withBody($contents, $mime)->put($uploadUrl);

        if (! $uploadResponse->successful()) {
            throw new RuntimeException('Could not upload image to LinkedIn.');
        }

        return $imageUrn;
    }

    /** @return array<string, string> */
    private function linkedInHeaders(): array
    {
        return [
            'LinkedIn-Version' => (string) config('services.linkedin-openid.api_version', '202601'),
            'X-Restli-Protocol-Version' => '2.0.0',
        ];
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

    private function extractPostId(string $postUrn): string
    {
        if (preg_match('/urn:li:share:(\d+)/', $postUrn, $matches)) {
            return $matches[1];
        }

        if (preg_match('/urn:li:ugcPost:(\d+)/', $postUrn, $matches)) {
            return $matches[1];
        }

        return $postUrn;
    }

    private function apiError(\Illuminate\Http\Client\Response $response, string $fallback): string
    {
        $message = $response->json('message') ?? $response->json('error.message') ?? $response->json('serviceErrorCode');

        if (is_string($message) && str_contains(strtolower($message), 'scope')) {
            return $fallback.' Reconnect LinkedIn and approve w_member_social permission. LinkedIn says: '.$message;
        }

        return is_string($message) && $message !== '' ? $fallback.' '.$message : $fallback;
    }
}
