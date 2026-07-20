<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandContentSuggestionService;
use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandProfileService;
use App\Application\Services\Brand\BrandSuggestionGenerationService;
use App\Application\Services\Brand\PlanAccessService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandContentSuggestionsController extends Controller
{
    public function __construct(
        private readonly BrandKnowledgeBaseService $knowledgeBase,
        private readonly BrandProfileService $profiles,
        private readonly BrandContentSuggestionService $suggestions,
        private readonly BrandSuggestionGenerationService $generation,
        private readonly PlanAccessService $planAccess,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $canAccess = $this->planAccess->canAccessContentSuggestions($request->user(), $brand);

        if (! $canAccess) {
            return view('app.brand.content-suggestions', [
                'brand' => $brand,
                'categories' => [],
                'kbReady' => false,
                'featureLocked' => true,
            ]);
        }

        $this->knowledgeBase->ensureTrained($brand);

        $brand->load(['voiceSettings', 'knowledgeBase', 'suggestedPrompts']);
        $profile = $this->profiles->build($brand);
        $categories = $this->suggestions->buildForBrand($brand, $profile['ai_analysis'] ?? []);

        if (! $this->planAccess->canAccessReels($request->user(), $brand)) {
            $categories = collect($categories)
                ->reject(fn (array $category) => ($category['key'] ?? '') === 'reel')
                ->values()
                ->all();
        }

        return view('app.brand.content-suggestions', [
            'brand' => $brand,
            'categories' => $categories,
            'kbReady' => ($profile['knowledge_base']?->training_status ?? '') === 'complete',
            'featureLocked' => false,
        ]);
    }

    public function generate(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->planAccess->canAccessContentSuggestions($request->user(), $request->attributes->get('current_brand'))) {
            return $this->upgradeResponse($request);
        }

        set_time_limit(900);

        $brand = $request->attributes->get('current_brand');

        $allowedCategories = $this->planAccess->canAccessReels($request->user(), $brand)
            ? 'caption,image,reel,carousel'
            : 'caption,image,carousel';

        $validated = $request->validate([
            'prompts' => ['required', 'array', 'min:1'],
            'prompts.*.category' => ['required', 'in:'.$allowedCategories],
            'prompts.*.title' => ['required', 'string', 'max:255'],
            'prompts.*.text' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $saved = $this->generation->generateFromPrompts($brand, $validated['prompts']);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()
                ->route('app.brand.content-suggestions')
                ->with('error', $e->getMessage());
        }

        if ($saved === 0) {
            $message = 'AI did not return any content. Try again or check storage/logs/laravel.log.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('app.brand.content-suggestions')
                ->with('error', $message);
        }

        $successMessage = $saved.' AI content item(s) added to Content Library.';
        $redirectUrl = route('app.brand.content-library', ['tab' => 'ai']);

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => $redirectUrl,
                'message' => $successMessage,
                'saved' => $saved,
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', $successMessage);
    }

    public function regeneratePrompt(Request $request): JsonResponse
    {
        if (! $this->planAccess->canAccessContentSuggestions($request->user(), $request->attributes->get('current_brand'))) {
            return response()->json([
                'message' => $this->planAccess->upgradeMessage(),
                'upgrade_url' => $this->planAccess->upgradeUrl(),
            ], 403);
        }

        set_time_limit(120);

        $brand = $request->attributes->get('current_brand');

        $allowedCategories = $this->planAccess->canAccessReels($request->user(), $brand)
            ? 'caption,image,reel,carousel'
            : 'caption,image,carousel';

        $validated = $request->validate([
            'category' => ['required', 'in:'.$allowedCategories],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $result = $this->generation->regeneratePromptText(
                $brand,
                $validated['category'],
                $validated['title'],
                $validated['text'],
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $result || blank($result['content'] ?? null)) {
            return response()->json([
                'message' => 'AI did not return a new prompt. Try again or check storage/logs/laravel.log.',
            ], 422);
        }

        return response()->json([
            'prompt' => $result['content'],
            'provider' => $result['provider'],
            'model' => $result['model'],
        ]);
    }

    private function upgradeResponse(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $this->planAccess->upgradeMessage(),
                'upgrade_url' => $this->planAccess->upgradeUrl(),
            ], 403);
        }

        return redirect()
            ->route('onboarding.plan')
            ->with('error', $this->planAccess->upgradeMessage());
    }
}
