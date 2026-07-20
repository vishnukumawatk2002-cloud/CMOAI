<?php

namespace App\Http\Middleware;

use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiSubscriptionActive
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $this->subscriptions->activeForUser($request->user());

        if (! $subscription?->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'An active subscription is required.',
            ], 403);
        }

        $request->attributes->set('subscription', $subscription);

        return $next($request);
    }
}
