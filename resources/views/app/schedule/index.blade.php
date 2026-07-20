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
    $prevMonth = $weekStart->copy()->subMonth()->format('Y-m');
    $nextMonth = $weekStart->copy()->addMonth()->format('Y-m');
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
        <div class="pub-month">
            <i class="ti ti-calendar-event"></i>
            <span>Publishing {{ $weekStart->format('F Y') }}</span>
        </div>
        <div class="pub-toolbar-actions">
            <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $prevWeek]) }}" class="pub-nav-btn" title="Previous week"><i class="ti ti-chevron-left"></i></a>
            <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d')]) }}" class="pub-today-btn">Today</a>
            <a href="{{ route('app.schedule.index', ['tab' => 'calendar', 'start' => $nextWeek]) }}" class="pub-nav-btn" title="Next week"><i class="ti ti-chevron-right"></i></a>
            <a href="{{ route('app.brand.post-planning') }}" class="btn btn-purple btn-sm"><i class="ti ti-plus"></i> New Post</a>
        </div>
    </div>

    <div class="pub-columns-wrap">
        <button type="button" class="pub-slider-arrow" id="pub-cols-prev" aria-label="Scroll left"><i class="ti ti-chevron-left"></i></button>
        <div class="pub-columns" id="pub-columns">
            @foreach ($calendarDays as $day)
                <section class="pub-col {{ $day['isToday'] ? 'is-today' : '' }}" data-date="{{ $day['key'] }}">
                    <header class="pub-col-head">
                        <div class="pub-col-date">
                            <strong>{{ $day['weekday'] }} {{ $day['day'] }}</strong>
                            <span>{{ $day['count'] }} {{ $day['count'] === 1 ? 'item' : 'items' }}</span>
                        </div>
                    </header>

                    <div class="pub-col-body">
                        <a href="{{ route('app.brand.post-planning') }}" class="pub-compose-btn">
                            <i class="ti ti-plus"></i> Compose
                        </a>

                        @forelse ($day['posts'] as $post)
                            @php $meta = $platformIcons[$post['platform']] ?? $platformIcons['x']; @endphp
                            <article class="pub-col-card">
                                <div class="pub-col-card-top">
                                    <div class="pub-col-card-meta">
                                        <i class="ti {{ $meta['icon'] }}" style="color:{{ $meta['color'] }}"></i>
                                        <span class="pub-col-time">{{ $post['time_label'] }}</span>
                                    </div>
                                    <span class="pub-status pub-status-{{ $post['status'] }}">{{ strtoupper($post['status_label']) }}</span>
                                </div>

                                <div class="pub-col-card-main">
                                    <div class="pub-col-card-copy">
                                        <h4>{{ $post['title'] }}</h4>
                                        <span class="pub-source">{{ $post['source'] }}</span>
                                        @if ($post['body'])
                                            <p>{{ $post['body'] }}</p>
                                        @endif
                                    </div>
                                    @if (! empty($post['thumbnail']))
                                        <div class="pub-col-thumb" style="background-image:url('{{ $post['thumbnail'] }}')"></div>
                                    @endif
                                </div>

                                <div class="pub-col-card-actions">
                                    <a href="{{ $post['show_url'] }}" class="pub-act-btn" title="View"><i class="ti ti-eye"></i></a>
                                    <a href="{{ $post['edit_url'] }}" class="pub-act-btn primary"><i class="ti ti-pencil"></i> Edit Post</a>
                                </div>
                            </article>
                        @empty
                            <div class="pub-open-slot">
                                <span>Open slot</span>
                                <a href="{{ route('app.brand.post-planning') }}">Compose</a>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
        <button type="button" class="pub-slider-arrow" id="pub-cols-next" aria-label="Scroll right"><i class="ti ti-chevron-right"></i></button>
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
            @if ($item->scheduled_at)
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

@if ($activeTab === 'calendar')
@push('scripts')
<script>
(() => {
    const board = document.getElementById('pub-columns');
    const prev = document.getElementById('pub-cols-prev');
    const next = document.getElementById('pub-cols-next');
    if (!board) return;

    const colStep = () => {
        const col = board.querySelector('.pub-col');
        return col ? col.offsetWidth + 14 : 320;
    };

    prev?.addEventListener('click', () => {
        board.scrollBy({ left: -colStep(), behavior: 'smooth' });
    });
    next?.addEventListener('click', () => {
        board.scrollBy({ left: colStep(), behavior: 'smooth' });
    });

    // Mouse / touch drag-to-slide
    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;
    let moved = false;

    const onStart = (clientX) => {
        isDown = true;
        moved = false;
        startX = clientX - board.getBoundingClientRect().left;
        scrollLeft = board.scrollLeft;
        board.classList.add('is-dragging');
    };

    const onMove = (clientX, event) => {
        if (!isDown) return;
        const x = clientX - board.getBoundingClientRect().left;
        const walk = (x - startX) * 1.15;
        if (Math.abs(walk) > 4) moved = true;
        board.scrollLeft = scrollLeft - walk;
        if (event) event.preventDefault();
    };

    const onEnd = () => {
        isDown = false;
        board.classList.remove('is-dragging');
    };

    board.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        if (e.target.closest('a, button, input, textarea, select')) return;
        onStart(e.clientX);
    });

    window.addEventListener('mousemove', (e) => onMove(e.clientX, e));
    window.addEventListener('mouseup', onEnd);

    board.addEventListener('touchstart', (e) => {
        if (!e.touches[0]) return;
        onStart(e.touches[0].clientX);
    }, { passive: true });

    board.addEventListener('touchmove', (e) => {
        if (!e.touches[0]) return;
        onMove(e.touches[0].clientX);
    }, { passive: true });

    board.addEventListener('touchend', onEnd);
    board.addEventListener('touchcancel', onEnd);

    // Prevent accidental click after drag
    board.addEventListener('click', (e) => {
        if (!moved) return;
        if (e.target.closest('a, button')) {
            e.preventDefault();
            e.stopPropagation();
        }
        moved = false;
    }, true);

    // Wheel: horizontal slide when holding Shift or trackpad sideways
    board.addEventListener('wheel', (e) => {
        const delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
        if (Math.abs(delta) < 1) return;
        if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
            e.preventDefault();
            board.scrollLeft += delta;
        } else if (Math.abs(e.deltaY) > 0 && !e.ctrlKey) {
            e.preventDefault();
            board.scrollLeft += e.deltaY;
        }
    }, { passive: false });

    const todayCol = board.querySelector('.pub-col.is-today');
    todayCol?.scrollIntoView({ behavior: 'auto', inline: 'center', block: 'nearest' });
})();
</script>
@endpush
@endif
