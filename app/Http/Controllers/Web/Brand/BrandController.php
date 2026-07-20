<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\DTOs\Brand\CreateBrandDTO;
use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandProfileService;
use App\Application\Services\Brand\BrandService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Brand\StoreBrandRequest;
use App\Models\Brand;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brands,
        private readonly BrandKnowledgeBaseService $knowledgeBase,
        private readonly BrandProfileService $profiles,
    ) {
    }

    public function create(): View
    {
        return view('onboarding.brand-create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('brands/logos', 'public')
            : null;

        try {
            $brand = $this->brands->create($request->user(), new CreateBrandDTO(
                name: $request->name,
                website: $request->website,
                industry: $request->industry,
                country: $request->country,
                language: $request->language,
                tone: $request->tone,
                logoPath: $logoPath,
            ));
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'name' => 'This brand already exists. Please use a different brand name.',
                    ]);
            }

            throw $e;
        }

        session([
            'current_brand_id' => $brand->id,
            'step' => 1,
        ]);

        return redirect()
            ->route('onboarding.plan')
            ->with('success', 'Brand created! Choose a plan to continue.');
    }

    public function switch(Request $request, int $brandId): RedirectResponse
    {
        $this->brands->switchBrand($request->user(), $brandId);

        return redirect()->route('app.brand.dashboard');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $brands = $user->brands()
            ->withCount([
                'socialAccounts',
                'contentItems as published_posts_count' => fn ($query) => $query->where('status', 'published'),
                'contentItems as posts_this_month_count' => fn ($query) => $query
                    ->where('status', 'published')
                    ->whereMonth('published_at', now()->month)
                    ->whereYear('published_at', now()->year),
            ])
            ->withSum('contentItems as total_reach', 'reach')
            ->with(['socialAccounts:id,brand_id,platform,status'])
            ->latest()
            ->get();

        $subscription = $user->subscriptions()->with('plan')->latest()->first();
        $activeCount = $brands->where('is_active', true)->count();

        return view('app.brand.index', compact('brands', 'subscription', 'activeCount'));
    }

    public function show(Request $request, Brand $brand): View
    {
        abort_unless($brand->user_id === $request->user()->id, 403);

        $this->knowledgeBase->ensureTrained($brand);

        $brand->load('voiceSettings', 'socialAccounts', 'assets', 'knowledgeBase', 'suggestedPrompts');

        $profile = $this->profiles->build($brand);

        return view('app.brand.show', compact('brand', 'profile'));
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        abort_unless($brand->user_id === $request->user()->id, 403);

        $this->brands->deleteBrand($request->user(), $brand);

        if ($this->brands->currentBrand($request->user())) {
            return redirect()
                ->route('app.brands.index')
                ->with('success', 'Brand deleted successfully.');
        }

        return redirect()
            ->route('onboarding.brand.create')
            ->with('success', 'Brand deleted. Create a new brand to continue.');
    }
}
