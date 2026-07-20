@extends('layouts.app')

@section('title', 'Brands — CMO AI')
@section('pageTitle', 'Brands')

@section('topbarExtra')
    <a href="{{ route('app.dashboard') }}?create_brand=1" class="btn btn-purple btn-sm"><i class="ti ti-plus"></i> New brand</a>
@endsection

@section('content')
@php
    $brandColors = ['#F59E0B', '#3B82F6', '#22C55E', '#EC4899', '#EF4444', '#8B5CF6', '#0EA5E9', '#F97316'];
    $industryIcons = [
        'SaaS / Tech' => '⚡',
        'E-commerce' => '🛒',
        'Real estate' => '🏠',
        'Healthcare' => '🏥',
        'Education' => '🎓',
        'Agency' => '🎯',
        'Food & Beverage' => '🧁',
        'Other' => '✨',
    ];
    $platformIcons = [
        'facebook' => 'ti-brand-facebook',
        'instagram' => 'ti-brand-instagram',
        'linkedin' => 'ti-brand-linkedin',
        'x' => 'ti-brand-x',
        'twitter' => 'ti-brand-x',
        'youtube' => 'ti-brand-youtube',
        'pinterest' => 'ti-brand-pinterest',
    ];
    $planName = $subscription?->plan?->name ?? 'Free plan';
@endphp

<div class="brands-page">
    <div class="brands-page-head">
        <div>
            <h1 class="brands-page-title">Brands</h1>
            <p class="brands-page-sub">
                {{ $activeCount }} active {{ Str::plural('brand', $activeCount) }}
                · {{ $brands->count() }} total
                · Last updated {{ now()->diffForHumans() }}
            </p>
        </div>
    </div>

    @if ($brands->isEmpty())
        <div class="brand-add-card">
            <a href="{{ route('app.dashboard') }}?create_brand=1" class="brand-add-card-inner">
                <i class="ti ti-plus"></i>
                <span>Create your first brand</span>
                <p>Tell CMO AI about your brand so it can learn its voice.</p>
            </a>
        </div>
    @else
        <div class="brands-grid">
            @foreach ($brands as $brand)
                @php
                    $color = $brandColors[abs(crc32($brand->name)) % count($brandColors)];
                    $icon = $industryIcons[$brand->industry] ?? strtoupper(substr($brand->name, 0, 1));
                    $reach = (int) ($brand->total_reach ?? 0);
                    $reachLabel = $reach >= 1000000
                        ? round($reach / 1000000, 1).'M'
                        : ($reach >= 1000 ? round($reach / 1000, 1).'K' : (string) $reach);
                    $isActive = $brand->is_active && $brand->setup_completed_at;
                    $isPaused = ! $brand->is_active;
                @endphp
                <article class="brand-card">
                    <div class="brand-card-menu">
                        <details class="brand-card-menu-details">
                            <summary aria-label="Brand actions"><i class="ti ti-dots-vertical"></i></summary>
                            <div class="brand-card-menu-pop">
                                <form method="POST" action="{{ route('app.brand.switch', $brand->id) }}">
                                    @csrf
                                    <button type="submit">Open workspace</button>
                                </form>
                                <a href="{{ route('app.brands.show', $brand) }}">View profile</a>
                                <form method="POST" action="{{ route('app.brands.destroy', $brand) }}" onsubmit="return confirm('Delete {{ $brand->name }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger">Delete</button>
                                </form>
                            </div>
                        </details>
                    </div>

                    <form method="POST" action="{{ route('app.brand.switch', $brand->id) }}" class="brand-card-open">
                        @csrf
                        <button type="submit" class="brand-card-body">
                            <div class="brand-card-header">
                                <div class="brand-card-logo" style="background:linear-gradient(135deg,{{ $color }},{{ $color }}dd)">
                                    @if ($brand->logo_path)
                                        <img src="{{ asset('storage/'.$brand->logo_path) }}" alt="">
                                    @else
                                        {{ is_string($icon) && mb_strlen($icon) <= 2 ? $icon : strtoupper(substr($brand->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="brand-card-info">
                                    <div class="brand-card-name">{{ $brand->name }}</div>
                                    <div class="brand-card-meta">{{ $brand->industry ?? 'Brand' }} · {{ $brand->country ?? '—' }}</div>
                                    <div class="brand-card-badges">
                                        <span class="brand-plan-badge">{{ $planName }}</span>
                                        @if ($brand->setup_completed_at)
                                            <span class="brand-plan-price">Setup complete</span>
                                        @else
                                            <span class="brand-plan-price">Step {{ $brand->setup_step ?? 1 }}/5</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="brand-status-dot {{ $isPaused ? 'paused' : ($isActive ? 'active' : 'setup') }}"></span>
                            </div>

                            <div class="brand-card-stats">
                                <div class="brand-card-stat">
                                    <div class="brand-card-stat-val">{{ $brand->posts_this_month_count ?? 0 }}</div>
                                    <div class="brand-card-stat-label">Posts/mo</div>
                                </div>
                                <div class="brand-card-stat">
                                    <div class="brand-card-stat-val">{{ $reachLabel }}</div>
                                    <div class="brand-card-stat-label">Reach</div>
                                </div>
                                <div class="brand-card-stat">
                                    <div class="brand-card-stat-val">{{ $brand->social_accounts_count ?? 0 }}</div>
                                    <div class="brand-card-stat-label">Accounts</div>
                                </div>
                            </div>

                            <div class="brand-card-footer">
                                <div class="brand-card-platforms">
                                    @forelse ($brand->socialAccounts->take(5) as $account)
                                        <span class="brand-plat-dot" title="{{ ucfirst($account->platform) }}">
                                            <i class="ti {{ $platformIcons[$account->platform] ?? 'ti-world' }}"></i>
                                        </span>
                                    @empty
                                        <span class="brand-card-no-social">No social accounts connected</span>
                                    @endforelse
                                </div>
                                <div class="brand-card-status">
                                    @if ($brand->setup_completed_at)
                                        <span class="status-pill active">Active</span>
                                    @else
                                        <span class="status-pill setup">In setup</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    </form>
                </article>
            @endforeach

            <a href="{{ route('app.dashboard') }}?create_brand=1" class="brand-add-card">
                <div class="brand-add-card-inner">
                    <i class="ti ti-plus"></i>
                    <span>Add brand</span>
                </div>
            </a>
        </div>
    @endif
</div>
@endsection
