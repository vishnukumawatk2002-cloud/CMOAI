<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

interface SubscriptionRepositoryInterface
{
    public function activeForUser(User $user): ?Subscription;

    public function findPlanBySlug(string $slug): ?Plan;

    public function createSubscription(User $user, Plan $plan, array $data): Subscription;

    public function getUsageForMonth(Subscription $subscription, int $year, int $month): array;
}
