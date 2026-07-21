@php
    use App\Models\ContentItem;

    $platformIcons = [
        'linkedin' => ['icon' => 'ti-brand-linkedin', 'color' => '#0A66C2'],
        'instagram' => ['icon' => 'ti-brand-instagram', 'color' => '#E1306C'],
        'facebook' => ['icon' => 'ti-brand-facebook', 'color' => '#1877F2'],
        'x' => ['icon' => 'ti-brand-x', 'color' => 'var(--text2)'],
        'twitter' => ['icon' => 'ti-brand-x', 'color' => 'var(--text2)'],
        'youtube' => ['icon' => 'ti-brand-youtube', 'color' => '#FF0000'],
        'pinterest' => ['icon' => 'ti-brand-pinterest', 'color' => '#E60023'],
        'threads' => ['icon' => 'ti-brand-threads', 'color' => 'var(--text)'],
    ];

    $queueDot = fn (ContentItem $item) => $item->scheduled_at && $item->scheduled_at->isPast()
        ? 'var(--success)'
        : 'var(--warning)';

    $itemTitle = fn (ContentItem $item) => ucfirst($item->platform ?? 'Post').' — '.\Illuminate\Support\Str::limit($item->title ?? $item->body, 48);

    $bulkDays = max(1, min(30, $approvedForBulk));
@endphp

@extends('layouts.app')

@section('title', 'Schedule — CMO AI')
@section('pageTitle', 'Schedule')

@section('topbarExtra')
    <a href="{{ route('app.brand.post-planning') }}" class="btn btn-green btn-sm"><i class="ti ti-plus"></i> New Post</a>
@endsection

@section('content')
@if ($approvedForBulk > 0)
<div class="sched-bulk-bar">
    <div>
        <h3>Bulk schedule ready</h3>
        <p>{{ $approvedForBulk }} approved posts waiting to be scheduled across your accounts</p>
    </div>
    <div class="bulk-stats">
        <div><div class="bs-n">{{ $approvedForBulk }}</div><div class="bs-l">Posts</div></div>
        <div><div class="bs-n">{{ $bulkAccounts }}</div><div class="bs-l">Accounts</div></div>
        <div><div class="bs-n">{{ $bulkDays }}</div><div class="bs-l">Days</div></div>
    </div>
    <form method="POST" action="{{ route('app.schedule.bulk') }}">
        @csrf
        <button type="submit" class="bulk-btn"><i class="ti ti-calendar-plus"></i> Bulk schedule now</button>
    </form>
</div>
@endif

<div class="sched-tab-row">
    <a href="{{ route('app.schedule.index', ['tab' => 'today', 'start' => $weekStart->format('Y-m-d')]) }}"
       class="sched-tab {{ $activeTab === 'today' ? 'on' : '' }}">Today ({{ $todayQueue->count() }})</a>
    <a href="{{ route('app.schedule.index', ['tab' => 'tomorrow', 'start' => $weekStart->format('Y-m-d')]) }}"
       class="sched-tab {{ $activeTab === 'tomorrow' ? 'on' : '' }}">Tomorrow ({{ $tomorrowQueue->count() }})</a>
    <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $weekStart->format('Y-m-d')]) }}"
       class="sched-tab {{ $activeTab === 'calendar' ? 'on' : '' }}">Calendar</a>
    <a href="{{ route('app.schedule.index', ['tab' => 'failed', 'start' => $weekStart->format('Y-m-d')]) }}"
       class="sched-tab {{ $activeTab === 'failed' ? 'on' : '' }}">Failed ({{ $failedItems->count() }})</a>
</div>

@if ($activeTab === 'calendar')
<div class="pub-board">
    <div class="pub-toolbar">
        <div class="pub-view-tabs">
            <span class="pub-view-tab on">Week</span>
        </div>
        <div class="pub-toolbar-actions">
            <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $prevWeek]) }}" class="pub-nav-btn" title="Previous week"><i class="ti ti-chevron-left"></i></a>
            <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => now()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d')]) }}" class="pub-today-btn">Today</a>
            <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $nextWeek]) }}" class="pub-nav-btn" title="Next week"><i class="ti ti-chevron-right"></i></a>
            <span class="pub-range-label">{{ $weekStart->format('F Y') }}</span>
            <a href="{{ route('app.brand.post-planning') }}" class="btn btn-purple btn-sm"><i class="ti ti-plus"></i> New Post</a>
        </div>
    </div>

    <div class="pub-week-grid-wrap">
        <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $prevWeek]) }}" class="pub-week-arrow" title="Previous week"><i class="ti ti-chevron-left"></i></a>

        <div class="pub-week-grid">
            @foreach ($calendarDays as $day)
                <section class="pub-week-col {{ $day['isToday'] ? 'is-today' : '' }}" data-date="{{ $day['key'] }}">
                    <header class="pub-week-head">
                        <span class="pub-week-day {{ $day['isToday'] ? 'is-active' : '' }}">{{ $day['label'] }}</span>
                    </header>

                    <div class="pub-week-body">
                        <a href="{{ route('app.brand.post-planning') }}" class="pub-week-compose" title="Compose">
                            <i class="ti ti-plus"></i>
                        </a>

                        @forelse ($day['posts'] as $post)
                            @include('app.schedule.partials.week-post-card', ['item' => $post, 'platformIcons' => $platformIcons])
                        @empty
                            <div class="pub-week-empty">
                                <span>No posts</span>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $nextWeek]) }}" class="pub-week-arrow" title="Next week"><i class="ti ti-chevron-right"></i></a>
    </div>

    <div class="pub-accounts card">
        <div class="card-title">Auto-publishing to</div>
        @forelse($socialAccounts as $account)
            @php $p = strtolower($account->platform ?? ''); $meta = $platformIcons[$p] ?? $platformIcons['x']; @endphp
            <div class="acct-row">
                <i class="ti {{ $meta['icon'] }}" style="color:{{ $meta['color'] }}"></i>
                <span style="color:var(--text2)">{{ ucfirst($account->platform) }} · {{ $account->account_handle ?: $account->account_name ?: $brand->name }}</span>
                <span class="active-pill">Active</span>
            </div>
        @empty
            <p class="sched-empty">No social accounts connected. <a href="{{ route('app.brand.social-accounts') }}">Connect accounts</a></p>
        @endforelse
    </div>
</div>
@else
<div class="card">
    <div class="card-title">
        @if ($activeTab === 'today') Today's queue
        @elseif ($activeTab === 'tomorrow') Tomorrow's queue
        @else Failed posts
        @endif
    </div>
    @php
        $listItems = match ($activeTab) {
            'today' => $todayQueue,
            'tomorrow' => $tomorrowQueue,
            default => $failedItems,
        };
    @endphp
    @forelse($listItems as $item)
        @php $p = strtolower($item->platform ?? ''); $meta = $platformIcons[$p] ?? $platformIcons['x']; @endphp
        <div class="sched-q-item">
            <span class="qi-dot" style="background:{{ $activeTab === 'failed' ? 'var(--danger)' : $queueDot($item) }}"></span>
            <span class="qi-title">{{ $itemTitle($item) }}</span>
            @if ($activeTab === 'failed' && ($reason = data_get($item->metadata, 'publish_failure_reason')))
                <span class="qi-time" style="color:var(--danger);max-width:280px;white-space:normal">{{ \Illuminate\Support\Str::limit($reason, 80) }}</span>
            @elseif ($item->scheduled_at)
                <span class="qi-time">{{ $item->scheduled_at->format('M j, g:i A') }}</span>
            @endif
            <i class="ti {{ $meta['icon'] }} qi-icon" style="color:{{ $meta['color'] }}"></i>
            <a href="{{ route('app.brand.ai-post-library.show', $item) }}" class="qi-link"><i class="ti ti-eye"></i></a>
        </div>
    @empty
        <p class="sched-empty">
            @if ($activeTab === 'failed')
                No failed posts.
            @else
                Nothing scheduled. <a href="{{ route('app.content.library') }}">Go to Post Library</a>
            @endif
        </p>
    @endforelse
</div>
@endif
@endsection
