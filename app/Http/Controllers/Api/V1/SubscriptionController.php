<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Subscription\SubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends ApiController
{
    public function __construct(private readonly SubscriptionRepositoryInterface $subscriptions)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->activeForUser($request->user());

        if (! $subscription) {
            return $this->error('No active subscription found.', 404);
        }

        $subscription->load('plan');

        return $this->success(new SubscriptionResource($subscription));
    }

    public function store(SubscribeRequest $request): JsonResponse
    {
        $plan = $this->subscriptions->findPlanBySlug($request->plan_slug);

        if (! $plan?->is_active) {
            return $this->error('Invalid plan selected.', 422);
        }

        $existing = $this->subscriptions->activeForUser($request->user());

        if ($existing?->isActive()) {
            return $this->error('You already have an active subscription.', 422);
        }

        $subscription = $this->subscriptions->createSubscription($request->user(), $plan, [
            'billing_cycle' => $request->input('billing_cycle', 'monthly'),
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
        ]);

        $subscription->load('plan');

        return $this->created(new SubscriptionResource($subscription), 'Subscription created successfully.');
    }
}
