<?php

namespace App\Http\Controllers\Web\Dashboard;

use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandService;
use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ContentItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BrandService $brands,
        private readonly ContentRepositoryInterface $content,
        private readonly BrandKnowledgeBaseService $knowledgeBase,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $showBrandModal = (bool) session()->pull('show_brand_modal')
            && ! $user->brands()->exists();

        $brandIds = $user->brands()->pluck('id');

        $totalBrands = $brandIds->count();
        $activeBrands = $user->brands()->where('is_active', true)->count();

        $totalPublished = ContentItem::query()
            ->whereIn('brand_id', $brandIds)
            ->where('status', 'published')
            ->count();

        $totalScheduled = ContentItem::query()
            ->whereIn('brand_id', $brandIds)
            ->where('status', 'scheduled')
            ->count();

        $totalDrafts = ContentItem::query()
            ->whereIn('brand_id', $brandIds)
            ->where('status', 'draft')
            ->count();

        $totalReach = (int) ContentItem::query()
            ->whereIn('brand_id', $brandIds)
            ->sum('reach');

        $brandStats = $user->brands()
            ->withCount([
                'contentItems as published_count' => fn ($query) => $query->where('status', 'published'),
                'contentItems as scheduled_count' => fn ($query) => $query->where('status', 'scheduled'),
            ])
            ->withSum('contentItems as reach_sum', 'reach')
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'industry' => $brand->industry,
                'published_count' => $brand->published_count,
                'scheduled_count' => $brand->scheduled_count,
                'reach' => (int) ($brand->reach_sum ?? 0),
            ]);

        $upcomingScheduled = ContentItem::query()
            ->with('brand:id,name')
            ->whereIn('brand_id', $brandIds)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        return view('app.dashboard.overview', [
            'showBrandModal' => $showBrandModal,
            'totalBrands' => $totalBrands,
            'activeBrands' => $activeBrands,
            'totalPublished' => $totalPublished,
            'totalScheduled' => $totalScheduled,
            'totalDrafts' => $totalDrafts,
            'totalReach' => $totalReach,
            'brandStats' => $brandStats,
            'upcomingScheduled' => $upcomingScheduled,
        ]);
    }

    public function brand(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        abort_unless($brand, 404);

        $this->knowledgeBase->ensureTrained($brand);
        $brand->load('knowledgeBase');
        $statusCounts = $this->content->countByStatus($brand->id);

        $todayQueue = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('status', 'scheduled')
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $reach = ContentItem::query()->where('brand_id', $brand->id)->sum('reach');
        $avgEngagement = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->whereNotNull('engagement_rate')
            ->avg('engagement_rate');

        return view('app.dashboard.brand', [
            'brand' => $brand,
            'knowledgeReady' => $brand->knowledgeBase?->training_status === 'complete',
            'statusCounts' => $statusCounts,
            'todayQueue' => $todayQueue,
            'showBrandModal' => false,
            'stats' => [
                'reach' => $reach,
                'engagement' => number_format($avgEngagement ?? 0, 1),
            ],
            'weeklyReach' => [32, 48, 56, 72, 66, 90, 100],
        ]);
    }
}
