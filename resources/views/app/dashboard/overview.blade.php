@extends('layouts.app')

@section('title', 'Dashboard — CMO AI')
@section('pageTitle', 'Dashboard')

@section('topbarExtra')
    <button type="button" class="btn btn-ghost btn-sm" data-open-brand-modal><i class="ti ti-plus"></i> New brand</button>
@endsection

@section('content')
@php
    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $brandColors = ['#F59E0B', '#3B82F6', '#22C55E', '#EC4899', '#EF4444', '#8B5CF6', '#0EA5E9', '#F97316'];
    $maxPublished = max($brandStats->max('published_count') ?? 0, 1);
    $maxScheduled = max($brandStats->max('scheduled_count') ?? 0, 1);
    $reachLabel = $totalReach >= 1000000
        ? round($totalReach / 1000000, 1).'M'
        : ($totalReach >= 1000 ? round($totalReach / 1000, 1).'K' : number_format($totalReach));
@endphp

<div class="overview-page">
    <div class="overview-head">
        <div>
            <h1 class="overview-title">{{ $greeting }}, {{ $user->first_name }} 👋</h1>
            <p class="overview-sub">All brands · {{ now()->format('F Y') }}</p>
        </div>
        <a href="{{ route('app.brands.index') }}" class="btn btn-ghost btn-sm">View all brands</a>
    </div>

    <div class="overview-metrics">
        <div class="overview-metric">
            <div class="overview-metric-icon" style="background:var(--purple-lt)"><i class="ti ti-building-store" style="color:var(--purple2)"></i></div>
            <div class="overview-metric-label">Total brands</div>
            <div class="overview-metric-val">{{ $totalBrands }}</div>
            <div class="overview-metric-change">{{ $activeBrands }} active</div>
        </div>
        <div class="overview-metric">
            <div class="overview-metric-icon" style="background:var(--green-lt)"><i class="ti ti-check" style="color:var(--green)"></i></div>
            <div class="overview-metric-label">Posts published</div>
            <div class="overview-metric-val">{{ number_format($totalPublished) }}</div>
            <div class="overview-metric-change">All brands · all time</div>
        </div>
        <div class="overview-metric">
            <div class="overview-metric-icon" style="background:#EFF6FF"><i class="ti ti-eye" style="color:#1D4ED8"></i></div>
            <div class="overview-metric-label">Combined reach</div>
            <div class="overview-metric-val">{{ $reachLabel }}</div>
            <div class="overview-metric-change">Across all brands</div>
        </div>
        <div class="overview-metric">
            <div class="overview-metric-icon" style="background:var(--warning-lt)"><i class="ti ti-calendar" style="color:var(--warning)"></i></div>
            <div class="overview-metric-label">Total scheduled</div>
            <div class="overview-metric-val">{{ number_format($totalScheduled) }}</div>
            <div class="overview-metric-change">Upcoming posts</div>
        </div>
        <div class="overview-metric">
            <div class="overview-metric-icon" style="background:var(--purple-lt)"><i class="ti ti-file-text" style="color:var(--purple2)"></i></div>
            <div class="overview-metric-label">Draft posts</div>
            <div class="overview-metric-val">{{ number_format($totalDrafts) }}</div>
            <div class="overview-metric-change">Needs review</div>
        </div>
    </div>

    <div class="overview-grid-2">
        <div class="card">
            <div class="card-title">Posts published per brand</div>
            @forelse ($brandStats->sortByDesc('published_count') as $stat)
                @php $color = $brandColors[abs(crc32($stat['name'])) % count($brandColors)]; @endphp
                <div class="overview-prog-row">
                    <div class="overview-prog-label">
                        <span class="overview-prog-dot" style="background:{{ $color }}">{{ strtoupper(substr($stat['name'], 0, 1)) }}</span>
                        <span>{{ \Illuminate\Support\Str::limit($stat['name'], 18) }}</span>
                    </div>
                    <div class="overview-prog-bar"><div class="overview-prog-fill" style="width:{{ round(($stat['published_count'] / $maxPublished) * 100) }}%;background:{{ $color }}"></div></div>
                    <div class="overview-prog-num">{{ $stat['published_count'] }}</div>
                </div>
            @empty
                <p class="overview-empty">No brands yet. Create your first brand to get started.</p>
            @endforelse
        </div>

        <div class="card">
            <div class="card-title">Scheduled posts per brand</div>
            @forelse ($brandStats->sortByDesc('scheduled_count') as $stat)
                @php $color = $brandColors[abs(crc32($stat['name'])) % count($brandColors)]; @endphp
                <div class="overview-prog-row">
                    <div class="overview-prog-label">
                        <span class="overview-prog-dot" style="background:{{ $color }}">{{ strtoupper(substr($stat['name'], 0, 1)) }}</span>
                        <span>{{ \Illuminate\Support\Str::limit($stat['name'], 18) }}</span>
                    </div>
                    <div class="overview-prog-bar"><div class="overview-prog-fill" style="width:{{ $maxScheduled ? round(($stat['scheduled_count'] / $maxScheduled) * 100) : 0 }}%;background:{{ $color }}"></div></div>
                    <div class="overview-prog-num">{{ $stat['scheduled_count'] }}</div>
                </div>
            @empty
                <p class="overview-empty">No scheduled posts yet.</p>
            @endforelse
        </div>
    </div>

    <div class="overview-grid-2">
        <div class="card">
            <div class="card-title">Upcoming scheduled posts</div>
            @forelse ($upcomingScheduled as $item)
                <div class="q-item">
                    <span class="q-dot" style="background:var(--warning)"></span>
                    <span class="q-title">
                        <strong>{{ $item->brand?->name ?? 'Brand' }}</strong> ·
                        {{ ucfirst($item->platform) }} — {{ \Illuminate\Support\Str::limit($item->title ?? $item->body, 40) }}
                    </span>
                    <span class="q-meta">{{ $item->scheduled_at?->format('M j · g:i A') }}</span>
                </div>
            @empty
                <p class="overview-empty">No upcoming scheduled posts.</p>
            @endforelse
        </div>

        <div class="card">
            <div class="card-title">Your brands</div>
            @forelse ($brandStats as $stat)
                @php $color = $brandColors[abs(crc32($stat['name'])) % count($brandColors)]; @endphp
                <div class="overview-brand-row">
                    <span class="overview-prog-dot" style="background:{{ $color }}">{{ strtoupper(substr($stat['name'], 0, 1)) }}</span>
                    <div class="overview-brand-info">
                        <div class="overview-brand-name">{{ $stat['name'] }}</div>
                        <div class="overview-brand-meta">{{ $stat['industry'] ?? 'Brand' }} · {{ $stat['scheduled_count'] }} scheduled</div>
                    </div>
                    <form method="POST" action="{{ route('app.brand.switch', $stat['id']) }}">
                        @csrf
                        <button type="submit" class="btn btn-purple btn-sm">Open</button>
                    </form>
                </div>
            @empty
                <p class="overview-empty">Create a brand to start publishing.</p>
            @endforelse
            <div style="margin-top:12px">
                <a href="{{ route('app.brands.index') }}" class="btn btn-ghost btn-sm">View all brands →</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
    @include('app.dashboard.partials.brand-modal')
@endpush

@push('scripts')
    @include('app.dashboard.partials.brand-modal-scripts')
@endpush
