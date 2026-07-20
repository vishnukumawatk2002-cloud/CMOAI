<?php

namespace App\Http\Middleware;

use App\Application\Services\Brand\BrandService;
use App\Application\Services\Brand\PlanAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBrandSelected
{
    public function __construct(
        private readonly BrandService $brands,
        private readonly PlanAccessService $planAccess,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $brand = $this->brands->currentBrand($user);
        $inBrandWorkspace = $this->isBrandWorkspace($request);

        if (! $brand && $inBrandWorkspace) {
            return redirect()->route('app.dashboard');
        }

        if ($brand) {
            $brand->loadMissing('plan');
            $request->attributes->set('current_brand', $brand);
            view()->share('currentBrand', $brand);
            // Feature locks follow this brand's plan_id (not account-level alone).
            view()->share($this->planAccess->viewFlags($user, $brand));
        }

        view()->share('inBrandWorkspace', $inBrandWorkspace);

        return $next($request);
    }

    private function isBrandWorkspace(Request $request): bool
    {
        return $request->routeIs(
            'app.brand.dashboard',
            'app.brand.data-sources',
            'app.brand.knowledge-base',
            'app.brand.content-suggestions',
            'app.brand.content-suggestions.generate',
            'app.brand.content-library',
            'app.brand.content-library.store',
            'app.brand.content-library.update-manual',
            'app.brand.content-library.destroy-manual',
            'app.brand.content-library.destroy-carousel-slot',
            'app.brand.content-library.destroy-ai',
            'app.brand.post-planning',
            'app.brand.post-planning.store',
            'app.brand.post-planning.update-manual',
            'app.brand.post-planning.destroy-manual',
            'app.brand.post-planning.destroy-carousel-slot',
            'app.brand.post-planning.destroy-ai',
            'app.brand.post-planning.save',
            'app.brand.ai-post-library',
            'app.brand.ai-post-library.show',
            'app.brand.ai-post-library.edit',
            'app.brand.ai-post-library.update',
            'app.brand.ai-post-library.approve',
            'app.brand.ai-post-library.destroy',
            'app.brand.assets.show',
            'app.content.*',
            'app.schedule.*',
            'app.analytics',
            'app.ai-generator.*',
            'app.brand.settings',
            'app.brand.settings.update',
            'app.brand.settings.destroy',
            'app.brand.social-accounts',
            'app.brands.show',
        );
    }
}
