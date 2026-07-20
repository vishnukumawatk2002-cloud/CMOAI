<?php

namespace App\Http\Middleware;

use App\Domain\Enums\PlanLimitType;
use App\Domain\Exceptions\PlanLimitExceededException;
use App\Application\Services\Brand\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    public function __construct(private readonly PlanLimitService $limits) {
    }

    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        try {
            $this->limits->assertWithinLimit(
                $request->user(),
                PlanLimitType::from($limitType),
            );
        } catch (PlanLimitExceededException $e) {
            if ($request->is('api/*') || $request->expectsJson()) {
                throw $e;
            }

            if ($request->routeIs('onboarding.brand.store')) {
                return back()
                    ->withInput()
                    ->with('error', 'Upgrade your plan')
                    ->with('upgrade_plan', true);
            }

            return redirect()
                ->route('onboarding.plan')
                ->with('error', $e->getMessage());
        }

        return $next($request);
    }
}
