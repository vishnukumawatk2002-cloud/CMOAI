<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\User;

class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function activeForUser(User $user): ?Subscription
    {
        return Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest()
            ->first();
    }

    public function findPlanBySlug(string $slug): ?Plan
    {
        return Plan::query()->where('slug', $slug)->where('is_active', true)->first();
    }

    public function createSubscription(User $user, Plan $plan, array $data): Subscription
    {
        return Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            ...$data,
        ]);
    }

    public function getUsageForMonth(Subscription $subscription, int $year, int $month): array
    {
        $usage = SubscriptionUsage::query()
            ->where('subscription_id', $subscription->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return [
            'posts_used' => $usage?->posts_used ?? 0,
            'brands_count' => $usage?->brands_count ?? 0,
            'social_accounts_count' => $usage?->social_accounts_count ?? 0,
        ];
    }
}
