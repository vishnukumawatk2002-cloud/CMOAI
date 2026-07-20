<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandDataSourceController extends Controller
{
    public function __construct(
        private readonly BrandKnowledgeBaseService $knowledgeBase,
        private readonly BrandProfileService $profiles,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $this->knowledgeBase->ensureTrained($brand);

        $brand->load(['voiceSettings', 'socialAccounts', 'assets', 'knowledgeBase', 'suggestedPrompts']);
        $profile = $this->profiles->build($brand);

        $assetCount = count($profile['step2']['assets']);
        $linkCount = ($profile['step3']['website'] ? 1 : 0) + count($profile['step3']['social_urls']);
        $referenceCount = count($profile['step4']['urls']);
        $socialCount = $profile['step5']['accounts']->count();
        $totalSources = $assetCount + $linkCount + $referenceCount + $socialCount;

        return view('app.brand.data-sources', [
            'brand' => $brand,
            'profile' => $profile,
            'stats' => [
                'total' => $totalSources,
                'assets' => $assetCount,
                'links' => $linkCount,
                'references' => $referenceCount,
                'social' => $socialCount,
            ],
        ]);
    }
}
