<?php

namespace App\Http\Controllers\Web\Analytics;

use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly ContentRepositoryInterface $content)
    {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $statusCounts = $this->content->countByStatus($brand->id);

        return view('app.analytics.index', [
            'stats' => [
                'reach' => ContentItem::query()->where('brand_id', $brand->id)->sum('reach'),
                'engagement' => number_format(ContentItem::query()->where('brand_id', $brand->id)->avg('engagement_rate') ?? 0, 1),
                'published' => $statusCounts['published'] ?? 0,
                'scheduled' => $statusCounts['scheduled'] ?? 0,
            ],
            'weeklyReach' => [32, 48, 56, 72, 66, 90, 100],
        ]);
    }
}
