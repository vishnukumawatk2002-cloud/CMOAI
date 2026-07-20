<?php



namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandContentLibraryService;
use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandProfileService;
use App\Application\Services\Brand\PlanAccessService;
use App\Application\Services\Brand\SocialAccountService;
use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandPostPlanningController extends BrandContentLibraryController
{

    protected string $librarySection = 'content_library';

    protected string $defaultTab = 'manual';

    protected string $viewName = 'app.brand.post-planning';



    protected string $routeName = 'app.brand.post-planning';



    protected string $storageFolder = 'library';



    /** @var array<string, string> */

    private array $visualCategoryByPostType = [

        'image' => 'image',

        'reel' => 'reel',

        'carousel' => 'carousel',

    ];



    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $planAccess = app(PlanAccessService::class);
        $canAccessReels = $planAccess->canAccessReels($request->user(), $brand);

        app(BrandKnowledgeBaseService::class)->ensureTrained($brand);

        $brand->load(['voiceSettings', 'knowledgeBase', 'suggestedPrompts']);

        $profile = app(BrandProfileService::class)->build($brand);
        $socialAccounts = app(SocialAccountService::class);

        $accounts = $brand->socialAccounts()
            ->where('status', 'active')
            ->orderBy('platform')
            ->orderBy('account_name')
            ->get();

        $connectedChannels = $accounts->map(fn (SocialAccount $account) => [
            'id' => $account->id,
            'platform' => $account->platform,
            'name' => $account->account_name,
            'subtitle' => $socialAccounts->accountSubtitle($account),
            'initials' => $socialAccounts->avatarInitials($account),
            'avatar_style' => $socialAccounts->avatarStyle($account->platform),
            'profile_image_url' => filled($account->profile_image_url) ? $account->profile_image_url : null,
        ])->values();

        $categories = app(BrandContentLibraryService::class)->buildPlanningCategories(
            $brand,
            $profile['ai_analysis'] ?? []
        );

        // Post planning / manual flow always keeps Reels. AI-only reel lock is on Generated AI tab.
        if (! $canAccessReels) {
            $categories = collect($categories)->map(function (array $category) {
                if (($category['key'] ?? '') !== 'reel') {
                    return $category;
                }

                $category['items'] = collect($category['items'] ?? [])
                    ->filter(fn (array $item) => ($item['source'] ?? '') === 'manual')
                    ->values()
                    ->all();

                return $category;
            })->values()->all();
        }

        return view($this->viewName, [
            'brand' => $brand,
            'tab' => 'planning',
            'planningMixedContent' => true,
            'routeName' => $this->routeName,
            'kbReady' => ($profile['knowledge_base']?->training_status ?? '') === 'complete',
            'aiProvider' => data_get($profile['ai_analysis'], 'provider', 'local'),
            'categories' => $categories,
            'connectedChannels' => $connectedChannels,
            'canAccessReels' => true,
            'canAccessAiReels' => $canAccessReels,
        ]);
    }



    public function savePlan(Request $request): RedirectResponse

    {

        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([

            'tab' => ['required', 'in:ai,manual,mixed'],

            'post_type' => ['required', 'in:image,reel,carousel'],

            'platforms' => ['nullable', 'array'],

            'platforms.*' => ['string', 'in:linkedin,instagram,x,facebook,youtube,snapchat'],

            'social_accounts' => ['nullable', 'array', 'min:1'],

            'social_accounts.*' => ['integer'],

            'items' => ['required', 'array', 'min:2'],

            'items.*.category' => ['required', 'in:caption,image,reel,carousel'],

            'items.*.title' => ['nullable', 'string', 'max:255'],

            'items.*.body' => ['nullable', 'string', 'max:10000'],

            'items.*.manual_type' => ['nullable', 'string', 'max:50'],

            'items.*.manual_key' => ['nullable', 'string', 'max:100'],

            'items.*.thumbnail_url' => ['nullable', 'string', 'max:2000'],

            'items.*.video_url' => ['nullable', 'string', 'max:2000'],

            'items.*.carousel_images' => ['nullable', 'array'],

            'items.*.carousel_images.*' => ['nullable', 'string', 'max:2000'],

        ]);



        if (empty($validated['social_accounts']) && empty($validated['platforms'])) {
            return redirect()
                ->route($this->routeName, ['tab' => $validated['tab']])
                ->with('error', 'Select at least one connected social account.');
        }



        $postType = $validated['post_type'];

        $visualCategory = $this->visualCategoryByPostType[$postType];

        $grouped = collect($validated['items'])->groupBy('category');



        $captionItem = $grouped->get('caption')?->first();

        $visualItem = $grouped->get($visualCategory)?->first();



        if (! $captionItem || ! $visualItem) {

            return redirect()

                ->route($this->routeName, ['tab' => $validated['tab']])

                ->with('error', 'Select one caption and one '.$this->visualLabel($visualCategory).' to create a combined post.');

        }



        $captionBody = trim((string) ($captionItem['body'] ?? ''));

        $captionTitle = trim((string) ($captionItem['title'] ?? ''));



        if ($captionBody === '' && $captionTitle === '') {

            return redirect()

                ->route($this->routeName, ['tab' => $validated['tab']])

                ->with('error', 'Selected caption has no text.');

        }



        $baseTitle = $captionTitle !== '' ? $captionTitle : ($visualItem['title'] ?? 'Combined post');

        $body = $captionBody !== '' ? $captionBody : $captionTitle;

        $thumbnail = filled($visualItem['thumbnail_url'] ?? null)

            ? $visualItem['thumbnail_url']

            : $this->resolvePlanningThumbnail($brand, $visualItem);



        $videoUrl = null;

        if ($postType === 'reel') {

            $videoUrl = filled($visualItem['video_url'] ?? null)

                ? $visualItem['video_url']

                : $this->resolvePlanningVideoUrl($brand, $visualItem);

        }



        $carouselImages = [];

        if ($postType === 'carousel') {

            $carouselImages = collect($visualItem['carousel_images'] ?? [])

                ->filter(fn ($url) => filled($url))

                ->values()

                ->all();



            if ($carouselImages === []) {

                $carouselImages = $this->resolvePlanningCarouselImages($brand, $visualItem);

            }



            $thumbnail = $carouselImages[0] ?? $thumbnail;

        }



        $saved = 0;

        $publishTargets = $this->resolvePublishTargets($brand, $validated);

        if ($publishTargets === []) {
            return redirect()
                ->route($this->routeName, ['tab' => $validated['tab']])
                ->with('error', 'Select at least one connected social account or platform.');
        }



        foreach ($publishTargets as $target) {

            $platform = $target['platform'];

            ContentItem::query()->create([

                'brand_id' => $brand->id,

                'content_type' => $this->contentTypeForPostType($postType),

                'platform' => $platform,

                'title' => ucfirst($platform === 'x' ? 'X' : $platform).' — '.Str::limit($baseTitle, 80),

                'body' => $body,

                'status' => 'draft',

                'metadata' => [

                    'from_post_planning' => true,

                    'combined_post' => true,

                    'post_type' => $postType,

                    'planning_tab' => $validated['tab'],

                    'planning_source' => $this->resolvePlanningSource($validated['tab'], $captionItem, $visualItem),

                    'social_account_id' => $target['social_account_id'],

                    'social_account_name' => $target['social_account_name'],

                    'caption_title' => $captionTitle,

                    'visual_title' => $visualItem['title'] ?? null,

                    'caption_manual_type' => $captionItem['manual_type'] ?? null,

                    'caption_manual_key' => $captionItem['manual_key'] ?? null,

                    'visual_manual_type' => $visualItem['manual_type'] ?? null,

                    'visual_manual_key' => $visualItem['manual_key'] ?? null,

                    'thumbnail_url' => $postType === 'reel' ? null : $thumbnail,

                    'video_url' => $videoUrl,

                    'carousel_images' => $postType === 'carousel' ? $carouselImages : null,

                ],

            ]);



            $saved++;

        }



        return redirect()

            ->route('app.brand.ai-post-library')

            ->with('success', $saved.' combined post(s) created for '.$saved.' connected account(s).');

    }



    /** @param array<string, mixed> $item */

    private function resolvePlanningThumbnail(Brand $brand, array $item): ?string

    {

        $manualType = (string) ($item['manual_type'] ?? $item['category'] ?? '');

        $manualKey = (string) ($item['manual_key'] ?? '');



        if ($manualType === 'image' && ctype_digit($manualKey)) {

            $asset = BrandAsset::query()

                ->where('brand_id', $brand->id)

                ->where('id', (int) $manualKey)

                ->first();



            return $asset ? route('app.brand.assets.show', $asset) : null;

        }



        if ($manualType === 'caption' && filled($manualKey)) {

            $query = BrandAsset::query()->where('brand_id', $brand->id);



            $assets = ctype_digit($manualKey)

                ? $query->where('id', (int) $manualKey)->get()

                : $query->where('metadata->content_group', $manualKey)->get();



            $image = $assets->first(fn (BrandAsset $asset) => data_get($asset->metadata, 'role') === 'image'

                || str_starts_with((string) $asset->file_type, 'image'));



            return $image ? route('app.brand.assets.show', $image) : null;

        }



        if ($manualType === 'carousel' && filled($manualKey)) {

            $asset = BrandAsset::query()

                ->where('brand_id', $brand->id)

                ->where('metadata->carousel_group', $manualKey)

                ->orderBy('metadata->slot')

                ->first();



            return $asset ? route('app.brand.assets.show', $asset) : null;

        }



        if ($manualType === 'reel' && ctype_digit($manualKey)) {

            $asset = BrandAsset::query()

                ->where('brand_id', $brand->id)

                ->where('id', (int) $manualKey)

                ->first();



            return $asset ? route('app.brand.assets.show', $asset) : null;

        }



        return null;

    }



    /** @param array<string, mixed> $item */

    private function resolvePlanningVideoUrl(Brand $brand, array $item): ?string

    {

        if (filled($item['video_url'] ?? null)) {

            return $item['video_url'];

        }



        $manualType = (string) ($item['manual_type'] ?? $item['category'] ?? '');

        $manualKey = (string) ($item['manual_key'] ?? '');



        if ($manualType === 'reel' && ctype_digit($manualKey)) {

            $asset = BrandAsset::query()

                ->where('brand_id', $brand->id)

                ->where('id', (int) $manualKey)

                ->first();



            return $asset ? route('app.brand.assets.show', $asset) : null;

        }



        if (filled($item['thumbnail_url'] ?? null)) {

            return $item['thumbnail_url'];

        }



        return $this->resolvePlanningThumbnail($brand, $item);

    }



    /** @param array<string, mixed> $item */

    /** @return list<string> */

    private function resolvePlanningCarouselImages(Brand $brand, array $item): array

    {

        $manualType = (string) ($item['manual_type'] ?? $item['category'] ?? '');

        $manualKey = (string) ($item['manual_key'] ?? '');



        if ($manualType === 'carousel' && filled($manualKey)) {

            return BrandAsset::query()

                ->where('brand_id', $brand->id)

                ->where('metadata->carousel_group', $manualKey)

                ->orderBy('metadata->slot')

                ->get()

                ->map(fn (BrandAsset $asset) => route('app.brand.assets.show', $asset))

                ->filter()

                ->values()

                ->all();

        }



        return [];

    }



    private function contentTypeForPostType(string $postType): string

    {

        return match ($postType) {

            'reel' => 'reel_script',

            'carousel' => 'carousel',

            default => 'post',

        };

    }



    private function visualLabel(string $category): string

    {

        return match ($category) {

            'reel' => 'Reels/Shorts video',

            'carousel' => 'carousel set',

            default => 'image',

        };

    }



    /** @param  array<string, mixed>  $captionItem
     * @param  array<string, mixed>  $visualItem
     */
    private function resolvePlanningSource(string $tab, array $captionItem, array $visualItem): string
    {
        if ($tab === 'ai') {
            return 'AI';
        }

        if ($tab === 'manual') {
            return 'Manual';
        }

        $captionManual = filled($captionItem['manual_key'] ?? null);
        $visualManual = filled($visualItem['manual_key'] ?? null);

        if ($captionManual && $visualManual) {
            return 'Manual';
        }

        if (! $captionManual && ! $visualManual) {
            return 'AI';
        }

        return 'Mixed';
    }



    /** @param  array<string, mixed>  $validated
     * @return list<array{platform: string, social_account_id: int|null, social_account_name: string|null}>
     */
    private function resolvePublishTargets(Brand $brand, array $validated): array
    {
        if (! empty($validated['social_accounts'])) {
            return SocialAccount::query()
                ->where('brand_id', $brand->id)
                ->where('status', 'active')
                ->whereIn('id', $validated['social_accounts'])
                ->orderBy('platform')
                ->orderBy('account_name')
                ->get()
                ->map(fn (SocialAccount $account) => [
                    'platform' => $account->platform,
                    'social_account_id' => $account->id,
                    'social_account_name' => $account->account_name,
                ])
                ->values()
                ->all();
        }

        $platforms = $validated['platforms'] ?? [];

        return collect($platforms)
            ->map(fn (string $platform) => [
                'platform' => $platform,
                'social_account_id' => null,
                'social_account_name' => null,
            ])
            ->values()
            ->all();
    }

}


