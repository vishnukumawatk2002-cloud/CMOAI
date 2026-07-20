<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose your plan — CMO AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @include('layouts.partials.head-assets')
</head>
<body class="onboarding-page">
    <header class="onboarding-header">
        <div class="onboarding-logo">
            <div class="onboarding-logo-i"><i class="ti ti-speakerphone" style="color:#fff"></i></div>
            CMO <span>AI</span>
        </div>
        <a href="{{ route('app.dashboard') }}" class="back-btn"><i class="ti ti-arrow-left"></i> Back</a>
    </header>

    <div class="pricing-page">
        <div class="pricing-head">
            <div class="eyebrow">Pricing</div>
            <h1>Choose your plan</h1>
            <p>Select a plan to unlock AI content generation, scheduling, and social publishing.</p>
            @if (session('success'))
                <div class="alert-bar success" style="margin:16px auto 0;max-width:520px">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert-bar" style="margin:16px auto 0;max-width:520px;background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA">{{ session('error') }}</div>
            @endif
            @if ($subscription ?? null)
                <div class="alert-bar success" style="margin:16px auto 0;max-width:520px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <span>Current plan: <strong>{{ $subscription->plan->name }}</strong></span>
                    <a href="{{ route('onboarding.wizard') }}" class="p-btn" style="padding:8px 16px;font-size:13px;white-space:nowrap">
                        Continue setup <i class="ti ti-arrow-right"></i>
                    </a>
                </div>
            @endif
            <div class="toggle-row">
                <button type="button" class="tog on" id="tm" onclick="toggleBilling('monthly')">Monthly</button>
                <button type="button" class="tog" id="ty" onclick="toggleBilling('yearly')">Yearly <span class="save-badge">Save 30%</span></button>
            </div>
        </div>

        <div class="plans-grid">
            @foreach($plans as $plan)
                @if($plan->slug === 'enterprise')
                <div class="p-card" style="border-style:dashed">
                    <p class="p-name">{{ $plan->name }}</p>
                    <p class="p-price" style="font-size:26px;margin-top:6px">Custom</p>
                    <p class="p-period">tailored pricing</p>
                    @if($plan->subtitle)
                        <p class="p-subtitle">{{ $plan->subtitle }}</p>
                    @endif
                    <ul class="p-feats">
                        <li><i class="ti ti-check"></i> Unlimited brand workspaces</li>
                        <li><i class="ti ti-check"></i> SSO / SAML</li>
                        <li><i class="ti ti-check"></i> Dedicated manager</li>
                        <li><i class="ti ti-check"></i> SLA guarantee</li>
                    </ul>
                    <a href="mailto:sales@cmoai.app" class="p-btn">Contact sales <i class="ti ti-arrow-right"></i></a>
                </div>
                @else
                <div class="p-card {{ $plan->slug === 'growth' ? 'popular' : '' }}">
                    @if($plan->slug === 'growth')
                        <div class="pop-badge">Most popular</div>
                    @endif
                    <p class="p-name">{{ $plan->name }}</p>
                    <p class="p-price">
                        <sup>₹</sup>
                        <span class="pn" data-m="{{ (int) $plan->price_monthly }}" data-y="{{ (int) $plan->price_yearly }}">{{ number_format($plan->price_monthly) }}</span>
                    </p>
                    <p class="p-period billing-label">per month</p>
                    @if($plan->subtitle)
                        <p class="p-subtitle">{{ $plan->subtitle }}</p>
                    @endif
                    <ul class="p-feats">
                        <li><i class="ti ti-check"></i> {{ $plan->formatLimit($plan->max_social_accounts) }} social accounts</li>
                        <li><i class="ti ti-check"></i> {{ $plan->formatLimit($plan->max_posts_per_month) }} posts / month</li>
                        @foreach($plan->editableFeatures() as $feature)
                            <li class="{{ $feature['enabled'] ? '' : 'p-feature-disabled' }}">
                                <i class="ti ti-{{ $feature['enabled'] ? 'check' : 'x' }}"></i>
                                {{ $feature['name'] }}
                            </li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('onboarding.plan.subscribe', $plan->slug) }}" class="plan-form">
                        @csrf
                        <input type="hidden" name="billing_cycle" value="monthly" class="billing-input">
                        <button type="submit" class="p-btn">
                            Get {{ $plan->name }} <i class="ti ti-arrow-right"></i>
                        </button>
                    </form>
                </div>
                @endif
            @endforeach
        </div>

        @php
            $paidPlans = $plans->where('slug', '!=', 'enterprise')->values();
            $comparisonFeatures = $paidPlans
                ->flatMap(fn ($plan) => $plan->editableFeatures())
                ->unique(fn (array $feature) => strtolower($feature['name']))
                ->values();
        @endphp
        @if($paidPlans->isNotEmpty())
        <div class="compare-table">
            <table>
                <thead>
                    <tr>
                        <th style="width:38%">Feature</th>
                        @foreach($paidPlans as $plan)
                            <th>{{ $plan->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Social accounts</td>
                        @foreach($paidPlans as $plan)<td>{{ $plan->formatLimit($plan->max_social_accounts) }}</td>@endforeach
                    </tr>
                    <tr>
                        <td>Posts per month</td>
                        @foreach($paidPlans as $plan)<td>{{ $plan->formatLimit($plan->max_posts_per_month) }}</td>@endforeach
                    </tr>
                    @foreach($comparisonFeatures as $comparisonFeature)
                    <tr>
                        <td>{{ $comparisonFeature['name'] }}</td>
                        @foreach($paidPlans as $plan)
                            @php
                                $planFeature = collect($plan->editableFeatures())->first(
                                    fn (array $feature) => strcasecmp($feature['name'], $comparisonFeature['name']) === 0
                                );
                                $enabled = (bool) ($planFeature['enabled'] ?? false);
                            @endphp
                            <td>
                                <i class="ti ti-{{ $enabled ? 'check ci' : 'x cx' }}"></i>
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="ent-bar">
            <div>
                <h3>Need something bigger?</h3>
                <p>Custom plans for large teams, agencies, and enterprise clients.</p>
            </div>
            <a href="mailto:sales@cmoai.app" class="ent-btn"><i class="ti ti-mail"></i> Talk to sales</a>
        </div>
    </div>

    <script>
    let billing = 'monthly';
    function toggleBilling(mode) {
        billing = mode;
        document.getElementById('tm').classList.toggle('on', mode === 'monthly');
        document.getElementById('ty').classList.toggle('on', mode === 'yearly');
        document.querySelectorAll('.pn').forEach(el => {
            const v = parseInt(mode === 'monthly' ? el.dataset.m : el.dataset.y);
            el.textContent = v.toLocaleString('en-IN');
        });
        document.querySelectorAll('.billing-label').forEach(el => {
            el.textContent = mode === 'monthly' ? 'per month' : 'per month (billed yearly)';
        });
        document.querySelectorAll('.billing-input').forEach(el => { el.value = mode; });
    }
    </script>
</body>
</html>
