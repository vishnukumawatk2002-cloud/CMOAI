<?php

namespace App\Application\Services\Brand;

use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Models\Brand;
use App\Models\Plan;
use App\Models\User;

class PlanAccessService
{
    public const TIER_STARTER = 'starter';

    public const TIER_GROWTH = 'growth';

    public const TIER_PRO = 'pro';

    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function upgradeMessage(): string
    {
        return 'Upgrade Plan for using this feature';
    }

    public function upgradeUrl(): string
    {
        return route('onboarding.plan');
    }

    public function planSlug(?User $user = null, ?Brand $brand = null): ?string
    {
        return $this->resolvePlan($user, $brand)?->slug;
    }

    /**
     * Tier map (by brand plan first, then user subscription):
     * - starter (~₹1000): AI KB / suggestions / generated library locked
     * - growth (~₹2500): full AI pages, AI Reels locked
     * - pro (~₹5000+ / agency / enterprise): everything
     */
    public function tier(?User $user = null, ?Brand $brand = null): string
    {
        $plan = $this->resolvePlan($user, $brand);

        if (! $plan) {
            return self::TIER_STARTER;
        }

        $slug = strtolower(trim((string) $plan->slug));
        $name = strtolower(trim((string) $plan->name));
        $price = (float) $plan->price_monthly;

        if (
            in_array($slug, ['enterprise', 'agency', 'pro'], true)
            || in_array($name, ['enterprise', 'pro', 'agency'], true)
        ) {
            return self::TIER_PRO;
        }

        if ($slug === 'growth' || $name === 'growth' || ($price >= 2000 && $price < 4500)) {
            return self::TIER_GROWTH;
        }

        if ($price >= 4500) {
            return self::TIER_PRO;
        }

        return self::TIER_STARTER;
    }

    public function isStarterPlan(?User $user = null, ?Brand $brand = null): bool
    {
        return $this->tier($user, $brand) === self::TIER_STARTER;
    }

    public function canAccessKnowledgeBase(?User $user = null, ?Brand $brand = null): bool
    {
        return ! $this->isStarterPlan($user, $brand);
    }

    public function canAccessContentSuggestions(?User $user = null, ?Brand $brand = null): bool
    {
        return ! $this->isStarterPlan($user, $brand);
    }

    public function canAccessGeneratedAiLibrary(?User $user = null, ?Brand $brand = null): bool
    {
        return ! $this->isStarterPlan($user, $brand);
    }

    public function canAccessReels(?User $user = null, ?Brand $brand = null): bool
    {
        return $this->tier($user, $brand) === self::TIER_PRO;
    }

    /** @return array<string, mixed> */
    public function viewFlags(?User $user = null, ?Brand $brand = null): array
    {
        $plan = $this->resolvePlan($user, $brand);

        return [
            'planTier' => $this->tier($user, $brand),
            'brandPlan' => $plan,
            'canAccessKnowledgeBase' => $this->canAccessKnowledgeBase($user, $brand),
            'canAccessContentSuggestions' => $this->canAccessContentSuggestions($user, $brand),
            'canAccessGeneratedAiLibrary' => $this->canAccessGeneratedAiLibrary($user, $brand),
            'canAccessReels' => $this->canAccessReels($user, $brand),
            'planUpgradeMessage' => $this->upgradeMessage(),
            'planUpgradeUrl' => $this->upgradeUrl(),
        ];
    }

    public function resolvePlan(?User $user = null, ?Brand $brand = null): ?Plan
    {
        $user ??= auth()->user();
        $brand ??= request()->attributes->get('current_brand');

        if ($brand instanceof Brand) {
            if ($brand->relationLoaded('plan') && $brand->plan) {
                return $brand->plan;
            }

            if ($brand->plan_id) {
                return Plan::query()->find($brand->plan_id);
            }
        }

        if (! $user) {
            return null;
        }

        return $this->subscriptions->activeForUser($user)?->plan;
    }
}
