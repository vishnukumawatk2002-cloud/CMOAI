<?php

namespace App\Http\Controllers\Web\Brand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\UpdateContentRequest;
use App\Application\Services\Brand\SocialAccountService;
use App\Application\Services\Brand\SocialConnectService;
use App\Application\Services\Brand\SocialPublishService;
use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandAiPostLibraryController extends Controller
{
    public function __construct(
        private readonly SocialPublishService $publisher,
        private readonly SocialAccountService $socialAccounts,
        private readonly SocialConnectService $socialConnect,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');

        return view('app.brand.ai-post-library.index', [
            'brand' => $brand,
            'items' => $this->queryItems($brand->id, $request),
            'statusCounts' => $this->countByStatus($brand->id),
        ]);
    }

    public function show(Request $request, ContentItem $contentItem): View
    {
        $this->authorizeAiPost($request, $contentItem);

        return view('app.brand.ai-post-library.show', [
            'brand' => $request->attributes->get('current_brand'),
            'item' => $this->attachPublishAccount($this->attachCarouselImages($contentItem->load('hashtags'))),
        ]);
    }

    public function edit(Request $request, ContentItem $contentItem): View
    {
        $this->authorizeAiPost($request, $contentItem);

        return view('app.brand.ai-post-library.edit', [
            'contentItem' => $contentItem,
        ]);
    }

    public function update(UpdateContentRequest $request, ContentItem $contentItem): RedirectResponse
    {
        $this->authorizeAiPost($request, $contentItem);

        $validated = $request->validated();
        $publishing = ($validated['status'] ?? $contentItem->status) === 'published'
            && $contentItem->status !== 'published';

        if ($publishing) {
            return $this->publishPost($request, $contentItem, $validated);
        }

        $contentItem->update($validated);

        return redirect()
            ->route('app.brand.ai-post-library')
            ->with('success', 'Post updated successfully.');
    }

    public function publish(Request $request, ContentItem $contentItem): RedirectResponse
    {
        $this->authorizeAiPost($request, $contentItem);

        if ($contentItem->status === 'published' && filled($contentItem->external_post_url)) {
            return redirect($contentItem->external_post_url);
        }

        return $this->publishPost($request, $contentItem);
    }

    /** @param  array<string, mixed>  $extraUpdates */
    private function publishPost(Request $request, ContentItem $contentItem, array $extraUpdates = []): RedirectResponse
    {
        if ($extraUpdates !== []) {
            unset($extraUpdates['status']);
            $contentItem->update($extraUpdates);
            $contentItem->refresh();
        }

        try {
            if (strtolower((string) $contentItem->platform) === 'facebook') {
                $this->socialConnect->refreshFacebookAccountsForBrand($request->attributes->get('current_brand'));
            }

            $this->publisher->publish($contentItem);
        } catch (\Throwable $e) {
            $contentItem->update(['status' => 'failed']);

            return redirect()
                ->route('app.brand.ai-post-library.show', $contentItem)
                ->with('error', 'Publish failed: '.$e->getMessage());
        }

        $message = 'Post published successfully.';
        $fresh = $contentItem->fresh();
        $platformLabel = strtolower((string) ($fresh?->platform ?? '')) === 'x'
            ? 'X'
            : ucfirst((string) ($fresh?->platform ?? 'social'));

        if ($fresh?->external_post_url) {
            $message .= ' View it on '.$platformLabel.'.';
        }

        if ($warning = session('publish_warning')) {
            $message .= ' '.$warning;
        }

        return redirect()
            ->route('app.brand.ai-post-library.show', $contentItem)
            ->with('success', $message);
    }

    public function destroy(Request $request, ContentItem $contentItem): RedirectResponse
    {
        $this->authorizeAiPost($request, $contentItem);

        $contentItem->delete();

        return redirect()
            ->route('app.brand.ai-post-library')
            ->with('success', 'Post deleted.');
    }

    public function approve(Request $request, ContentItem $contentItem): RedirectResponse
    {
        $this->authorizeAiPost($request, $contentItem);

        $contentItem->update(['status' => 'approved']);

        return back()->with('success', 'Post approved.');
    }

    private function queryItems(int $brandId, Request $request): LengthAwarePaginator
    {
        $query = ContentItem::query()
            ->where('brand_id', $brandId)
            ->where('metadata->from_post_planning', true);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('post_type') && in_array($request->post_type, ['image', 'reel', 'carousel'], true)) {
            $contentTypeMap = [
                'image' => 'post',
                'reel' => 'reel_script',
                'carousel' => 'carousel',
            ];

            $postType = $request->post_type;

            $query->where(function ($q) use ($postType, $contentTypeMap) {
                $q->where('metadata->post_type', $postType)
                    ->orWhere('content_type', $contentTypeMap[$postType]);
            });
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%");
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        $accountIds = collect($paginator->items())
            ->map(fn (ContentItem $item) => data_get($item->metadata, 'social_account_id'))
            ->filter()
            ->unique()
            ->values();

        $accounts = SocialAccount::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        return $paginator->through(function (ContentItem $item) use ($accounts) {
            $accountId = data_get($item->metadata, 'social_account_id');
            $account = $accountId ? $accounts->get($accountId) : null;

            return $this->attachPublishAccount($this->attachCarouselImages($item), $account);
        });
    }

    private function attachPublishAccount(ContentItem $item, ?SocialAccount $account = null): ContentItem
    {
        $platform = strtolower($item->platform ?? 'instagram');
        $accountId = data_get($item->metadata, 'social_account_id');

        if (! $account && $accountId) {
            $account = SocialAccount::query()->find($accountId);
        }

        $name = data_get($item->metadata, 'social_account_name') ?: $account?->account_name;
        $handle = $account?->account_handle;
        $initialsAccount = $account ?? new SocialAccount([
            'account_name' => $name,
            'account_handle' => $handle,
        ]);

        $item->setAttribute('publish_account', [
            'name' => $name,
            'handle' => filled($handle) ? $handle : null,
            'initials' => $name ? $this->socialAccounts->avatarInitials($initialsAccount) : strtoupper(substr($platform, 0, 1)),
            'avatar_style' => $this->socialAccounts->avatarStyle($platform),
            'profile_image_url' => filled($account?->profile_image_url) ? $account->profile_image_url : null,
            'external_id' => filled($account?->external_id) ? $account->external_id : null,
            'platform_label' => match ($platform) {
                'x' => 'X',
                'youtube' => 'YouTube',
                default => ucfirst($platform),
            },
        ]);

        return $item;
    }

    private function attachCarouselImages(ContentItem $item): ContentItem
    {
        $item->setAttribute('carousel_images', $this->resolveCarouselImages($item));

        return $item;
    }

    /** @return list<string> */
    private function resolveCarouselImages(ContentItem $item): array
    {
        $stored = collect(data_get($item->metadata, 'carousel_images', []))
            ->filter(fn ($url) => filled($url))
            ->values()
            ->all();

        if ($stored !== []) {
            return $stored;
        }

        $postType = data_get($item->metadata, 'post_type');
        $isCarousel = $postType === 'carousel' || $item->content_type === 'carousel';

        if (! $isCarousel) {
            return [];
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type', 'carousel');

        if ($manualType === 'carousel' && filled($manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where('metadata->carousel_group', $manualKey)
                ->orderBy('metadata->slot')
                ->get()
                ->map(fn (BrandAsset $asset) => route('app.brand.assets.show', $asset))
                ->filter()
                ->values()
                ->all();
        }

        $thumb = data_get($item->metadata, 'thumbnail_url');

        return filled($thumb) ? [$thumb] : [];
    }

    /** @return array<string, int> */
    private function countByStatus(int $brandId): array
    {
        return ContentItem::query()
            ->where('brand_id', $brandId)
            ->where('metadata->from_post_planning', true)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function authorizeAiPost(Request $request, ContentItem $contentItem): void
    {
        $brand = $request->attributes->get('current_brand');

        if ($contentItem->brand_id !== $brand->id || ! data_get($contentItem->metadata, 'from_post_planning')) {
            abort(403);
        }
    }
}
