<?php

namespace App\Http\Controllers\Web\Schedule;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');

        $month = Carbon::createFromFormat(
            'Y-m',
            $request->input('month', now()->format('Y-m'))
        )->startOfMonth();

        $weekStart = $this->resolveWeekStart($request, $month);

        $rangeStart = $weekStart->copy()->startOfDay();
        $rangeEnd = $weekStart->copy()->addDays(13)->endOfDay();

        $scheduledInRange = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->whereIn('status', ['scheduled', 'published', 'failed'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (ContentItem $item) => $item->scheduled_at->format('Y-m-d'));

        $calendarDays = $this->buildDayColumns($weekStart, 14, $scheduledInRange);

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
            return now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        }

        return $month->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
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
                'weekday' => strtoupper($cursor->format('D')),
                'isToday' => $cursor->isToday(),
                'count' => $items->count(),
                'posts' => $items->map(fn (ContentItem $item) => $this->mapPostCard($item))->values()->all(),
            ];

            $cursor->addDay();
        }

        return $columns;
    }

    /** @return array<string, mixed> */
    private function mapPostCard(ContentItem $item): array
    {
        $platform = strtolower((string) ($item->platform ?? 'x'));
        $status = strtolower((string) ($item->status ?? 'scheduled'));

        return [
            'id' => $item->id,
            'platform' => $platform,
            'platform_label' => $this->platformLabel($platform, $item->content_type),
            'platform_class' => $this->platformCalClass($platform),
            'title' => trim((string) ($item->title ?: Str::limit((string) $item->body, 60))) ?: 'Untitled post',
            'body' => Str::limit(trim((string) ($item->body ?? '')), 100),
            'time' => $item->scheduled_at?->format('H:i'),
            'time_label' => $item->scheduled_at?->format('H:i'),
            'status' => $status,
            'status_label' => match ($status) {
                'published' => 'Published',
                'failed' => 'Failed',
                default => 'Pending',
            },
            'thumbnail' => data_get($item->metadata, 'thumbnail_url'),
            'source' => data_get($item->metadata, 'planning_source', 'Manual'),
            'show_url' => route('app.brand.ai-post-library.show', $item),
            'edit_url' => route('app.brand.ai-post-library.edit', $item),
        ];
    }

    private function platformCalClass(?string $platform): string
    {
        return match (strtolower($platform ?? '')) {
            'linkedin' => 'li',
            'instagram' => 'ig',
            'facebook' => 'fb',
            'x', 'twitter' => 'x',
            default => 'x',
        };
    }

    private function platformLabel(?string $platform, ?string $contentType = null): string
    {
        $name = match (strtolower($platform ?? '')) {
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'x', 'twitter' => 'X',
            'youtube' => 'YouTube',
            default => ucfirst($platform ?? 'Post'),
        };

        if ($contentType && str_contains(strtolower($contentType), 'thread')) {
            return $name === 'X' ? 'X thread' : $name;
        }

        if ($contentType && str_contains(strtolower($contentType), 'reel')) {
            return 'Reel';
        }

        return $name;
    }
}
