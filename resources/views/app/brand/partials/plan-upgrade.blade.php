<div class="plan-upgrade-panel">
    <div class="plan-upgrade-icon"><i class="ti ti-lock"></i></div>
    <h2 class="plan-upgrade-title">{{ $planUpgradeMessage ?? 'Upgrade Plan for using this feature' }}</h2>
    <p class="plan-upgrade-sub">
        This feature is available on Growth (₹2,500) and Pro (₹5,000) plans.
        @php $activePlan = $brandPlan ?? $currentBrand?->plan ?? $subscription?->plan; @endphp
        @if ($activePlan)
            This brand plan: <strong>{{ $activePlan->name }}</strong>
            (₹{{ number_format((float) $activePlan->price_monthly) }}).
        @endif
        Upgrade to unlock AI tools for your brand.
    </p>
    <a href="{{ $planUpgradeUrl ?? route('onboarding.plan') }}" class="btn btn-purple">
        View plans <i class="ti ti-arrow-right"></i>
    </a>
</div>
