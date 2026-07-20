@php
    $badgeMap = [
        'draft' => 'badge-gray',
        'approved' => 'badge-green',
        'scheduled' => 'badge-yellow',
        'published' => 'badge-purple',
        'failed' => 'badge-red',
    ];
    $iconMap = [
        'linkedin' => 'ti-brand-linkedin',
        'instagram' => 'ti-brand-instagram',
        'facebook' => 'ti-brand-facebook',
        'x' => 'ti-brand-x',
        'youtube' => 'ti-brand-youtube',
    ];
    $counts = $statusCounts ?? [];
    $total = array_sum($counts);
@endphp

@extends('layouts.app')

@section('title', 'Post Library — CMO AI')
@section('pageTitle', 'Post Library')

@section('topbarExtra')
    <form method="GET" action="{{ route('app.content.library') }}" class="search-box">
        <i class="ti ti-search" style="color:var(--text3)"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search content…">
    </form>
    <a href="{{ route('app.content.generate') }}" class="btn btn-green btn-sm"><i class="ti ti-plus"></i> Generate new</a>
@endsection

@section('content')
<div class="filter-bar">
    @foreach(['' => 'All', 'draft' => 'Drafts', 'approved' => 'Approved', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $key => $label)
        @php $count = $key === '' ? $total : ($counts[$key] ?? 0); @endphp
        <a href="{{ route('app.content.library', array_merge(request()->except('status', 'page'), $key ? ['status' => $key] : [])) }}"
           class="fp {{ request('status', '') === $key ? 'on' : '' }}">{{ $label }} ({{ $count }})</a>
    @endforeach
    <div class="sep"></div>
    <form method="GET" action="{{ route('app.content.library') }}" style="display:inline">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
        <select name="platform" class="fs" onchange="this.form.submit()">
            <option value="">All platforms</option>
            @foreach(['linkedin','instagram','x','facebook','youtube'] as $p)
                <option value="{{ $p }}" @selected(request('platform') === $p)>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </form>
</div>

<form id="bulk-form" method="POST" action="{{ route('app.content.bulk') }}">
    @csrf
    <div class="bulk-bar" id="bulk-bar" style="display:none">
        <span id="sel-count">0 items selected</span>
        <button type="submit" name="action" value="approve" class="bb green"><i class="ti ti-check"></i> Approve all</button>
        <button type="button" class="bb" onclick="clearSel()"><i class="ti ti-x"></i> Clear</button>
    </div>

    <div class="grid">
        @forelse($items as $item)
        <div class="cc" data-id="{{ $item->id }}">
            <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="bulk-check" style="display:none">
            <div class="cc-head">
                <div class="cc-type">
                    <i class="ti {{ $iconMap[$item->platform] ?? 'ti-file-text' }}"></i>
                    {{ ucfirst($item->platform) }} · {{ ucfirst(str_replace('_', ' ', $item->content_type ?? 'post')) }}
                </div>
                <span class="badge {{ $badgeMap[$item->status] ?? 'badge-gray' }}">{{ ucfirst($item->status) }}</span>
            </div>
            <div class="cc-body">
                <p class="cc-text">{{ $item->body }}</p>
                @if($item->hashtags->isNotEmpty())
                <div class="cc-tags">
                    @foreach($item->hashtags as $tag)
                        <span class="cc-tag">#{{ ltrim($tag->hashtag, '#') }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="cc-foot">
                <div class="cc-meta">
                    @if($item->scheduled_at)
                        <i class="ti ti-calendar"></i> {{ $item->scheduled_at->format('M j, g:i A') }}
                    @else
                        <i class="ti ti-clock"></i> {{ $item->created_at->diffForHumans() }}
                    @endif
                </div>
                <div class="cc-acts">
                    <a href="{{ route('app.content.edit', $item) }}" class="ca" title="Edit"><i class="ti ti-edit"></i></a>
                    <form method="POST" action="{{ route('app.content.destroy', $item) }}" style="display:inline" onsubmit="return confirm('Delete this content?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="ca" title="Delete"><i class="ti ti-trash"></i></button>
                    </form>
                    @if($item->status === 'draft')
                    <form method="POST" action="{{ route('app.content.update', $item) }}" style="display:inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="ca green" title="Approve"><i class="ti ti-check"></i></button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="card" style="grid-column:1/-1;text-align:center;padding:48px">
            <i class="ti ti-folder-off" style="font-size:40px;color:var(--text3)"></i>
            <p style="margin-top:12px;color:var(--text2)">No content yet. <a href="{{ route('app.content.generate') }}" style="color:var(--purple2)">Generate your first post</a></p>
        </div>
        @endforelse
    </div>
</form>

@if($items->hasPages())
<div style="margin-top:20px">{{ $items->withQueryString()->links('pagination::bootstrap-5') }}</div>
@endif
@endsection

@push('scripts')
<script>
const sel = new Set();
document.querySelectorAll('.cc').forEach(card => {
    card.addEventListener('click', e => {
        if (e.target.closest('a,button,form')) return;
        toggleSel(card);
    });
});
function toggleSel(c) {
    const id = c.dataset.id;
    const cb = c.querySelector('.bulk-check');
    if (sel.has(id)) { sel.delete(id); c.classList.remove('selected'); cb.checked = false; }
    else { sel.add(id); c.classList.add('selected'); cb.checked = true; }
    const b = document.getElementById('bulk-bar');
    if (sel.size > 0) {
        b.style.display = 'flex';
        document.getElementById('sel-count').textContent = sel.size + ' item' + (sel.size > 1 ? 's' : '') + ' selected';
    } else b.style.display = 'none';
}
function clearSel() {
    sel.clear();
    document.querySelectorAll('.cc.selected').forEach(c => { c.classList.remove('selected'); c.querySelector('.bulk-check').checked = false; });
    document.getElementById('bulk-bar').style.display = 'none';
}
</script>
@endpush
