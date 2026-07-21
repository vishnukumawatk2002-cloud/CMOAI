@extends('layouts.app')

@section('title', ($brand->name ?? 'Brand').' — Dashboard')
@section('pageTitle', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $counts = collect($statusCounts ?? []);
    $drafts = $counts->get('draft', 0);
    $approved = $counts->get('approved', 0);
    $scheduled = $counts->get('scheduled', 0);
    $published = $counts->get('published', 0);
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $weeklyHeights = $weeklyReach ?? [32, 48, 56, 72, 66, 90, 100];
@endphp 

<div class="welcome-bar">
    <div>
        <div class="wb-h">{{ $greeting }}, {{ $user->first_name }} 👋</div>
        <div class="wb-p">
            @if ($knowledgeReady ?? false)
                CMO AI knows your brand · {{ $scheduled }} posts scheduled · Ready to generate content
            @else
                {{ $scheduled }} posts scheduled · CMO AI is learning your brand in the background
            @endif
        </div>
    </div>
    <div class="wb-actions">
        <button type="button" class="btn btn-ghost btn-sm" data-open-brand-modal><i class="ti ti-plus"></i> New brand</button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="sc-label"><i class="ti ti-eye"></i> Total reach</div>
        <div class="sc-num">{{ number_format($stats['reach'] ?? 0) }}</div>
        <div class="sc-trend"><i class="ti ti-trending-up"></i> +34% this month</div>
    </div>
    <div class="stat-card">
        <div class="sc-label"><i class="ti ti-send"></i> Posts published</div>
        <div class="sc-num">{{ $published }}</div>
        <div class="sc-trend"><i class="ti ti-trending-up"></i> All time</div>
    </div>
    <div class="stat-card">
        <div class="sc-label"><i class="ti ti-heart"></i> Engagement rate</div>
        <div class="sc-num">{{ $stats['engagement'] ?? '0' }}%</div>
        <div class="sc-trend"><i class="ti ti-trending-up"></i> Brand average</div>
    </div>
    <div class="stat-card">
        <div class="sc-label"><i class="ti ti-calendar"></i> Scheduled</div>
        <div class="sc-num">{{ $scheduled }}</div>
        <div class="sc-trend neutral">Next 30 days</div>
    </div>
</div>

<div class="dash-grid">
    <div>
        <div class="card" style="margin-bottom:12px">
            <div class="card-title">Reach this week <a href="{{ route('app.analytics') }}">View all →</a></div>
            <div class="chart">
                @foreach ($weeklyHeights as $i => $h)
                    <div class="bar {{ $i >= 3 ? 'hi' : '' }}" style="height:{{ $h }}%"></div>
                @endforeach
            </div>
            <div class="chart-labels"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
        </div>
        <div class="card">
            <div class="card-title">Publishing queue — Today <a href="{{ route('app.schedule.index') }}">Manage →</a></div>
            @forelse ($todayQueue as $item)
            <div class="q-item">
                <span class="q-dot" style="background:var(--warning)"></span>
                <span class="q-title">{{ ucfirst($item->platform) }} — {{ \Illuminate\Support\Str::limit($item->title ?? $item->body, 50) }}</span>
                <span class="q-meta">{{ $item->scheduled_at?->format('g:i A') }}</span>
                <i class="ti ti-brand-{{ $item->platform === 'x' ? 'x' : $item->platform }} q-icon"></i>
            </div>
            @empty
            <p style="padding:16px 0;color:var(--text3);font-size:13px;text-align:center">No posts scheduled for today.</p>
            @endforelse
        </div>
    </div>
    <div>
        <div class="card" style="margin-bottom:12px">
            <div class="card-title">Quick actions</div>
            <div class="act-grid">
                <a href="{{ route('app.schedule.index') }}" class="act-card"><i class="ti ti-calendar" style="color:var(--green)"></i> Schedule posts</a>
                <a href="{{ route('app.brand.content-library') }}" class="act-card"><i class="ti ti-folder" style="color:var(--warning)"></i> Content Library</a>
                <button type="button" class="act-card" style="border:none;width:100%;cursor:pointer" data-open-brand-modal><i class="ti ti-plus" style="color:var(--purple2)"></i> New brand</button>
            </div>
            <div class="insight-box" style="margin-top:14px">
                <div class="insight-title"><i class="ti ti-sparkles"></i> AI insights</div>
                <div class="ir"><i class="ti ti-clock"></i> Post Tue & Thu at 7–9 AM for best reach</div>
                <div class="ir"><i class="ti ti-trophy"></i> LinkedIn is your top platform this week</div>
                <div class="ir"><i class="ti ti-bulb"></i> Carousels get 3× more saves than single posts</div>
            </div>
        </div>
        <div class="card">
            <div class="card-title">Content by status</div>
            <div class="status-mini">
                <div class="status-row"><div><span class="status-dot" style="background:var(--purple)"></span>Drafts</div><strong style="color:var(--text)">{{ $drafts }}</strong></div>
                <div class="status-row"><div><span class="status-dot" style="background:var(--green)"></span>Approved</div><strong style="color:var(--text)">{{ $approved }}</strong></div>
                <div class="status-row"><div><span class="status-dot" style="background:var(--warning)"></span>Scheduled</div><strong style="color:var(--text)">{{ $scheduled }}</strong></div>
                <div class="status-row"><div><span class="status-dot" style="background:var(--success)"></span>Published</div><strong style="color:var(--text)">{{ $published }}</strong></div>
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
