<?php

namespace App\Http\Middleware;

use App\Application\Services\Brand\PlanAccessService;
use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly PlanAccessService $planAccess,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $subscription = $this->subscriptions->activeForUser($user);

        if (! $subscription?->isActive()) {
            if ($request->routeIs('app.dashboard') && ! $user->brands()->exists()) {
                return $next($request);
            }

            return redirect()->route('onboarding.plan');
        }

        $request->attributes->set('subscription', $subscription);
        view()->share('subscription', $subscription);
        view()->share($this->planAccess->viewFlags($user));

        return $next($request);
    }
}
