<?php

namespace App\Http\Controllers\Web\Schedule;

use App\Application\Services\Brand\SocialAccountService;
use App\Http\Controllers\Controller;
use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly SocialAccountService $socialAccounts,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');

        $month = Carbon::createFromFormat(
            'Y-m',
            $request->input('month', now()->format('Y-m'))
        )->startOfMonth();

        $weekStart = $this->resolveWeekStart($request, $month);

        $rangeStart = $weekStart->copy()->startOfDay();
        $rangeEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $scheduledItems = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->whereIn('status', ['scheduled', 'published', 'failed'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
            ->orderBy('scheduled_at')
            ->get();

        $accounts = SocialAccount::query()
            ->whereIn('id', $scheduledItems
                ->map(fn (ContentItem $item) => data_get($item->metadata, 'social_account_id'))
                ->filter()
                ->unique()
                ->values())
            ->get()
            ->keyBy('id');

        $scheduledInRange = $scheduledItems
            ->map(fn (ContentItem $item) => $this->enrichContentItem($item, $accounts))
            ->groupBy(fn (ContentItem $item) => $item->scheduled_at->format('Y-m-d'));

        $calendarDays = $this->buildDayColumns($weekStart, 7, $scheduledInRange);

        $todayQueue = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('status', 'scheduled')
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();

        $tomorrowQueue = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('status', 'scheduled')
            ->whereDate('scheduled_at', today()->addDay())
            ->orderBy('scheduled_at')
            ->get();

        $failedItems = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('status', 'failed')
            ->latest()
            ->limit(20)
            ->get();

        $approvedForBulk = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('status', 'approved')
            ->count();

        $socialAccounts = SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('status', 'active')
            ->orderBy('platform')
            ->get();

        $activeTab = $request->input('tab', 'calendar');
        if (! in_array($activeTab, ['today', 'tomorrow', 'calendar', 'failed'], true)) {
            $activeTab = 'calendar';
        }

        return view('app.schedule.index', [
            'brand' => $brand,
            'month' => $month,
            'weekStart' => $weekStart,
            'calendarDays' => $calendarDays,
            'todayQueue' => $todayQueue,
            'tomorrowQueue' => $tomorrowQueue,
            'failedItems' => $failedItems,
            'approvedForBulk' => $approvedForBulk,
            'socialAccounts' => $socialAccounts,
            'activeTab' => $activeTab,
            'bulkAccounts' => max(1, $socialAccounts->count()),
            'prevWeek' => $weekStart->copy()->subDays(7)->format('Y-m-d'),
            'nextWeek' => $weekStart->copy()->addDays(7)->format('Y-m-d'),
            'weekEnd' => $weekStart->copy()->addDays(6),
        ]);
    }

    public function bulkSchedule(Request $request): RedirectResponse
    {
        return back()->with('success', 'Posts scheduled successfully.');
    }

    private function resolveWeekStart(Request $request, Carbon $month): Carbon
    {
        $startParam = $request->input('start');

        if (is_string($startParam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startParam)) {
            return Carbon::createFromFormat('Y-m-d', $startParam)->startOfDay();
        }

        if ($month->isSameMonth(now())) {
            return now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        }

        return $month->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
    }

    /** @param Collection<string, Collection<int, ContentItem>> $grouped */
    private function buildDayColumns(Carbon $start, int $days, Collection $grouped): array
    {
        $columns = [];
        $cursor = $start->copy();

        for ($i = 0; $i < $days; $i++) {
            $key = $cursor->format('Y-m-d');
            $items = $grouped->get($key) ?? collect();

            $columns[] = [
                'key' => $key,
                'date' => $cursor->copy(),
                'day' => $cursor->day,
                'weekday' => $cursor->format('D'),
                'weekdayShort' => strtoupper($cursor->format('D')),
                'label' => $cursor->format('D j'),
                'isToday' => $cursor->isToday(),
                'count' => $items->count(),
                'posts' => $items->values()->all(),
            ];

            $cursor->addDay();
        }

        return $columns;
    }

    private function enrichContentItem(ContentItem $item, Collection $accounts): ContentItem
    {
        $platform = strtolower((string) ($item->platform ?? 'instagram'));
        $accountId = data_get($item->metadata, 'social_account_id');
        $account = $accountId ? $accounts->get($accountId) : null;

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
            'platform_label' => match ($platform) {
                'x' => 'X',
                'youtube' => 'YouTube',
                default => ucfirst($platform),
            },
        ]);

        $item->setAttribute('schedule_media', $this->resolveScheduleMedia($item));

        return $item;
    }

    /** @return array{thumbnail: ?string, video: ?string, carousel: list<string>} */
    private function resolveScheduleMedia(ContentItem $item): array
    {
        $postType = data_get($item->metadata, 'post_type', 'image');
        $thumbnail = data_get($item->metadata, 'thumbnail_url');
        $video = data_get($item->metadata, 'video_url');
        $carousel = collect(data_get($item->metadata, 'carousel_images', []))
            ->filter(fn ($url) => filled($url))
            ->values()
            ->all();

        $manualType = (string) data_get($item->metadata, 'visual_manual_type', '');
        $manualKey = data_get($item->metadata, 'visual_manual_key');

        if (filled($manualKey)) {
            if ($postType === 'reel' && ! filled($video) && $manualType === 'reel' && ctype_digit((string) $manualKey)) {
                $asset = BrandAsset::query()
                    ->where('brand_id', $item->brand_id)
                    ->where('id', (int) $manualKey)
                    ->first();
                $video = $asset ? route('app.brand.assets.show', $asset) : $video;
            }

            if (! filled($thumbnail) && $postType !== 'reel') {
                $thumbnail = $this->resolveAssetUrl($item->brand_id, $manualType, $manualKey);
            }

            if ($postType === 'carousel' && $carousel === []) {
                $carousel = BrandAsset::query()
                    ->where('brand_id', $item->brand_id)
                    ->where('metadata->carousel_group', $manualKey)
                    ->orderBy('metadata->slot')
                    ->get()
                    ->map(fn (BrandAsset $asset) => route('app.brand.assets.show', $asset))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        if ($postType === 'carousel' && $carousel !== [] && ! filled($thumbnail)) {
            $thumbnail = $carousel[0];
        }

        return [
            'thumbnail' => filled($thumbnail) ? (string) $thumbnail : null,
            'video' => filled($video) ? (string) $video : null,
            'carousel' => $carousel,
        ];
    }

    private function resolveAssetUrl(int $brandId, string $manualType, mixed $manualKey): ?string
    {
        if ($manualType === 'image' && ctype_digit((string) $manualKey)) {
            $asset = BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('id', (int) $manualKey)
                ->first();

            return $asset ? route('app.brand.assets.show', $asset) : null;
        }

        if ($manualType === 'caption' && filled($manualKey)) {
            $query = BrandAsset::query()->where('brand_id', $brandId);
            $assets = ctype_digit((string) $manualKey)
                ? $query->where('id', (int) $manualKey)->get()
                : $query->where('metadata->content_group', $manualKey)->get();

            $image = $assets->first(fn (BrandAsset $asset) => data_get($asset->metadata, 'role') === 'image'
                || str_starts_with((string) $asset->file_type, 'image'));

            return $image ? route('app.brand.assets.show', $image) : null;
        }

        if ($manualType === 'carousel' && filled($manualKey)) {
            $asset = BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('metadata->carousel_group', $manualKey)
                ->orderBy('metadata->slot')
                ->first();

            return $asset ? route('app.brand.assets.show', $asset) : null;
        }

        return null;
    }
}
