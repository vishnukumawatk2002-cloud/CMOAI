<?php

namespace App\Application\Services\Brand;

use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Enums\PlanLimitType;
use App\Domain\Exceptions\PlanLimitExceededException;
use App\Models\User;

class PlanLimitService
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function assertWithinLimit(User $user, PlanLimitType $type): void
    {
        // Brand count is no longer limited by plan.
        if ($type === PlanLimitType::Brands) {
            return;
        }

        $subscription = $this->subscriptions->activeForUser($user);

        if (! $subscription?->plan) {
            throw new PlanLimitExceededException($type->value, 0, 'No active subscription. Please choose a plan.');
        }

        $limit = match ($type) {
            PlanLimitType::SocialAccounts => $subscription->plan->max_social_accounts,
            PlanLimitType::PostsPerMonth => $subscription->plan->max_posts_per_month,
            PlanLimitType::Brands => null,
        };

        if ($limit === null) {
            return;
        }

        $current = match ($type) {
            PlanLimitType::SocialAccounts => 0, // resolved per brand in SocialAccountService
            PlanLimitType::PostsPerMonth => $this->getPostsUsedThisMonth($subscription),
            PlanLimitType::Brands => 0,
        };

        if ($current >= $limit) {
            throw new PlanLimitExceededException($type->value, $limit);
        }
    }

    private function getPostsUsedThisMonth($subscription): int
    {
        $usage = $this->subscriptions->getUsageForMonth(
            $subscription,
            (int) now()->format('Y'),
            (int) now()->format('n'),
        );

        return $usage['posts_used'];
    }
}
