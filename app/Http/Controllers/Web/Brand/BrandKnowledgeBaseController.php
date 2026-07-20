<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandProfileService;
use App\Application\Services\Brand\PlanAccessService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandKnowledgeBaseController extends Controller
{
    public function __construct(
        private readonly BrandKnowledgeBaseService $knowledgeBase,
        private readonly BrandProfileService $profiles,
        private readonly PlanAccessService $planAccess,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $canAccess = $this->planAccess->canAccessKnowledgeBase($request->user(), $brand);

        if ($canAccess) {
            $this->knowledgeBase->ensureTrained($brand);
        }

        $brand->load(['voiceSettings', 'socialAccounts', 'assets', 'knowledgeBase', 'suggestedPrompts']);
        $profile = $canAccess
            ? $this->profiles->build($brand)
            : [
                'knowledge_base' => $brand->knowledgeBase,
                'suggested_prompts' => collect(),
                'ai_analysis' => [],
            ];

        return view('app.brand.knowledge-base', [
            'brand' => $brand,
            'profile' => $profile,
            'featureLocked' => ! $canAccess,
        ]);
    }

    public function regenerate(Request $request): JsonResponse
    {
        if (! $this->planAccess->canAccessKnowledgeBase($request->user(), $request->attributes->get('current_brand'))) {
            return response()->json([
                'message' => $this->planAccess->upgradeMessage(),
                'upgrade_url' => $this->planAccess->upgradeUrl(),
            ], 403);
        }

        set_time_limit(300);

        $brand = $request->attributes->get('current_brand');

        if (! $this->knowledgeBase->forceRetrain($brand)) {
            return response()->json([
                'message' => 'AI regeneration failed. Check storage/logs/laravel.log and your API key.',
            ], 422);
        }

        $brand->load(['knowledgeBase', 'suggestedPrompts']);
        $profile = $this->profiles->build($brand);
        $kb = $profile['knowledge_base'];
        $ai = $profile['ai_analysis'] ?? [];

        return response()->json([
            'message' => 'Knowledge base regenerated successfully.',
            'ai_analysis' => $ai,
            'suggested_prompts' => ($profile['suggested_prompts'] ?? collect())->map(fn ($prompt) => [
                'id' => $prompt->id,
                'label' => $prompt->label,
                'prompt_text' => $prompt->prompt_text,
                'platform' => $prompt->platform,
                'content_type' => $prompt->content_type,
            ])->values(),
            'knowledge_base' => [
                'training_status' => $kb?->training_status,
                'last_trained_at' => $kb?->last_trained_at?->format('M j, Y g:i A'),
                'detected_tone' => $kb?->detected_tone ?? ($ai['detected_tone'] ?? null),
                'detected_audience' => $kb?->detected_audience ?? ($ai['detected_audience'] ?? null),
                'detected_services' => $kb?->detected_services ?? ($ai['detected_services'] ?? null),
                'top_keywords' => $kb?->top_keywords ?? ($ai['top_keywords'] ?? []),
            ],
        ]);
    }
}
