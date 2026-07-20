@php
    $platformIcons = [
        'linkedin' => ['icon' => 'ti-brand-linkedin', 'color' => '#0A66C2'],
        'instagram' => ['icon' => 'ti-brand-instagram', 'color' => '#E1306C'],
        'facebook' => ['icon' => 'ti-brand-facebook', 'color' => '#1877F2'],
        'x' => ['icon' => 'ti-brand-x', 'color' => '#111827'],
        'youtube' => ['icon' => 'ti-brand-youtube', 'color' => '#FF0000'],
        'snapchat' => ['icon' => 'ti-brand-snapchat', 'color' => '#FFFC00'],
    ];

    $statusLabels = [
        'draft' => ['label' => 'Draft', 'class' => 'draft'],
        'approved' => ['label' => 'Approved', 'class' => 'approved'],
        'scheduled' => ['label' => 'Scheduled', 'class' => 'scheduled'],
        'published' => ['label' => 'Published', 'class' => 'published'],
        'failed' => ['label' => 'Failed', 'class' => 'failed'],
    ];

    $postTypeLabels = [
        'image' => 'Post',
        'reel' => 'Reel script',
        'carousel' => 'Carousel',
        'post' => 'Post',
        'reel_script' => 'Reel script',
    ];

    $resolvePostTypeLabel = function ($item) use ($postTypeLabels) {
        $fromPlanning = data_get($item->metadata, 'post_type');
        if ($fromPlanning && isset($postTypeLabels[$fromPlanning])) {
            return $postTypeLabels[$fromPlanning];
        }

        $contentType = $item->content_type ?? 'post';

        return $postTypeLabels[$contentType] ?? ucfirst(str_replace('_', ' ', $contentType));
    };

    $counts = $statusCounts ?? [];
    $total = array_sum($counts);
@endphp

@extends('layouts.app')

@section('title', 'Ai Post Library — '.$brand->name)
@section('pageTitle', 'Ai Post Library')

@section('topbarExtra')
    <form method="GET" action="{{ route('app.brand.ai-post-library') }}" class="search-box">
        <i class="ti ti-search" style="color:var(--text3)"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search posts…">
    </form>
    <a href="{{ route('app.brand.post-planning') }}" class="btn btn-ghost btn-sm"><i class="ti ti-calendar-event"></i> Post planning</a>
@endsection

@section('content')
<div class="filter-bar">
    @foreach(['' => 'All', 'draft' => 'Draft', 'approved' => 'Approved', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $key => $label)
        @php $count = $key === '' ? $total : ($counts[$key] ?? 0); @endphp
        <a href="{{ route('app.brand.ai-post-library', array_merge(request()->except('status', 'page'), $key ? ['status' => $key] : [])) }}"
           class="fp {{ request('status', '') === $key ? 'on' : '' }}">{{ $label }} ({{ $count }})</a>
    @endforeach
    <div class="sep"></div>
    <form method="GET" action="{{ route('app.brand.ai-post-library') }}" class="apl-filter-form">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
        <select name="platform" class="fs" onchange="this.form.submit()">
            <option value="">All platforms</option>
            @foreach(['instagram','x','linkedin','facebook','youtube','snapchat'] as $p)
                <option value="{{ $p }}" @selected(request('platform') === $p)>{{ ucfirst($p === 'x' ? 'X' : $p) }}</option>
            @endforeach
        </select>
        <select name="post_type" class="fs" onchange="this.form.submit()">
            <option value="">All post types</option>
            <option value="image" @selected(request('post_type') === 'image')>Image post</option>
            <option value="reel" @selected(request('post_type') === 'reel')>Reels post</option>
            <option value="carousel" @selected(request('post_type') === 'carousel')>Carousel post</option>
        </select>
    </form>
</div>

<div class="apl-grid">
    @forelse($items as $item)
        @php
            $platform = strtolower($item->platform ?? 'instagram');
            $pMeta = $platformIcons[$platform] ?? $platformIcons['instagram'];
            $status = $statusLabels[$item->status] ?? $statusLabels['draft'];
            $thumb = data_get($item->metadata, 'thumbnail_url');
            $postTypeKey = data_get($item->metadata, 'post_type', ($item->content_type ?? '') === 'reel_script' ? 'reel' : 'image');
            $isVideoPost = $postTypeKey === 'reel' || ($item->content_type ?? '') === 'reel_script';
            $isCarouselPost = $postTypeKey === 'carousel' || ($item->content_type ?? '') === 'carousel';
            $carouselImages = $item->carousel_images ?? [];
            $videoUrl = data_get($item->metadata, 'video_url') ?? ($isVideoPost ? $thumb : null);
            $sourceTag = data_get($item->metadata, 'planning_source', 'Manual');
            $displayTime = $item->scheduled_at
                ? $item->scheduled_at->format('M j, g:i A')
                : $item->created_at->diffForHumans();
            $timeIcon = $item->scheduled_at ? 'ti-calendar' : 'ti-clock';
            $isPublished = $item->status === 'published';
            $postTypeLabel = $resolvePostTypeLabel($item);
            $publishAccount = $item->publish_account ?? [];
        @endphp
        <article class="apl-card">
            <div class="apl-head">
                @include('app.brand.ai-post-library.partials.card-channel-head', [
                    'item' => $item,
                    'platform' => $platform,
                    'pMeta' => $pMeta,
                    'postTypeLabel' => $postTypeLabel,
                    'displayTime' => $displayTime,
                    'timeIcon' => $timeIcon,
                    'publishAccount' => $publishAccount,
                ])
                <span class="apl-status apl-status-{{ $status['class'] }}">{{ strtoupper($status['label']) }}</span>
            </div>

            <div class="apl-body">
                <div class="apl-body-main">
                    <h3 class="apl-title">{{ \Illuminate\Support\Str::limit($item->title ?? 'Untitled post', 42) }}</h3>
                    <span class="apl-source-tag">{{ $sourceTag }}</span>
                    <p class="apl-desc">{{ \Illuminate\Support\Str::limit($item->body, 120) }}</p>
                </div>
                <div class="apl-thumb {{ $isVideoPost && $videoUrl ? 'apl-thumb--video' : '' }} {{ $isCarouselPost && count($carouselImages) ? 'apl-thumb--carousel' : '' }}">
                    @if($isCarouselPost && count($carouselImages))
                        @include('app.brand.ai-post-library.partials.carousel-slider', ['images' => $carouselImages, 'size' => 'thumb'])
                    @elseif($isVideoPost && $videoUrl)
                        <div class="apl-thumb-video">
                            <video src="{{ $videoUrl }}" muted playsinline preload="metadata"></video>
                            <button type="button" class="apl-thumb-play apl-video-play" data-video-url="{{ $videoUrl }}" title="Play video">
                                <i class="ti ti-player-play"></i>
                            </button>
                        </div>
                    @elseif($thumb)
                        <img src="{{ $thumb }}" alt="">
                    @else
                        <div class="apl-thumb-placeholder" style="background:linear-gradient(135deg,#FFF7ED,#FEF3C7)">
                            <i class="ti ti-photo"></i>
                        </div>
                    @endif
                </div>
            </div>

            <div class="apl-foot">
                <a href="{{ route('app.brand.ai-post-library.show', $item) }}" class="apl-icon-btn" title="Preview"><i class="ti ti-eye"></i></a>
                <a href="{{ route('app.brand.ai-post-library.show', $item) }}" class="apl-primary-btn">
                    {{ $isPublished ? 'View Post' : 'Preview Post' }}
                </a>
                <div class="apl-menu-wrap">
                    <button type="button" class="apl-icon-btn apl-menu-toggle" title="More"><i class="ti ti-dots"></i></button>
                    <div class="apl-menu">
                        <a href="{{ route('app.brand.ai-post-library.edit', $item) }}"><i class="ti ti-pencil"></i> Edit</a>
                        @if($item->status === 'draft')
                            <form method="POST" action="{{ route('app.brand.ai-post-library.approve', $item) }}">
                                @csrf
                                <button type="submit"><i class="ti ti-check"></i> Approve</button>
                            </form>
                        @endif
                        @if(strtolower($item->platform ?? '') === 'facebook' && $item->status !== 'published')
                            <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}">
                                @csrf
                                <button type="submit"><i class="ti ti-send"></i> Publish to Facebook</button>
                            </form>
                        @endif
                        @if(strtolower($item->platform ?? '') === 'instagram' && $item->status !== 'published')
                            <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}">
                                @csrf
                                <button type="submit"><i class="ti ti-send"></i> Publish to Instagram</button>
                            </form>
                        @endif
                        @if(strtolower($item->platform ?? '') === 'linkedin' && $item->status !== 'published')
                            <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}">
                                @csrf
                                <button type="submit"><i class="ti ti-send"></i> Publish to LinkedIn</button>
                            </form>
                        @endif
                        @if(strtolower($item->platform ?? '') === 'x' && $item->status !== 'published')
                            <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}">
                                @csrf
                                <button type="submit"><i class="ti ti-send"></i> Publish to X</button>
                            </form>
                        @endif
                        @if(strtolower($item->platform ?? '') === 'youtube' && $item->status !== 'published')
                            @if($isVideoPost)
                                <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}">
                                    @csrf
                                    <button type="submit"><i class="ti ti-send"></i> Publish YouTube Short</button>
                                </form>
                            @else
                                @php
                                    $ytId = $publishAccount['external_id'] ?? null;
                                    $ytPosts = filled($ytId) && ! str_starts_with((string) $ytId, 'demo-')
                                        ? 'https://www.youtube.com/channel/'.$ytId.'/posts'
                                        : 'https://studio.youtube.com';
                                @endphp
                                <a href="{{ $ytPosts }}" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> Open YouTube Posts</a>
                            @endif
                        @endif
                        @if(strtolower($item->platform ?? '') === 'snapchat' && $item->status !== 'published')
                            <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}">
                                @csrf
                                <button type="submit"><i class="ti ti-send"></i> Publish to Snapchat</button>
                            </form>
                        @endif
                        @if($item->status === 'published' && filled($item->external_post_url))
                            @php $viewPlatform = strtolower($item->platform ?? '') === 'x' ? 'X' : ucfirst($item->platform ?? 'social'); @endphp
                            <a href="{{ $item->external_post_url }}" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> View on {{ $viewPlatform }}</a>
                        @endif
                        <form method="POST" action="{{ route('app.brand.ai-post-library.destroy', $item) }}" onsubmit="return confirm('Delete this post?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="danger"><i class="ti ti-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="apl-empty card">
            <i class="ti ti-layout-grid"></i>
            <h3>No posts in AI Post Library yet</h3>
            <p>Select content in <strong>Post planning</strong>, check the boxes, and click <strong>Save</strong>.</p>
            <a href="{{ route('app.brand.post-planning') }}" class="btn btn-green btn-sm"><i class="ti ti-calendar-event"></i> Go to Post planning</a>
        </div>
    @endforelse
</div>

@if($items->hasPages())
<div style="margin-top:20px">{{ $items->withQueryString()->links('pagination::bootstrap-5') }}</div>
@endif

<div id="apl-video-modal" class="apl-video-modal" hidden>
    <div class="apl-video-modal-backdrop" data-close-video></div>
    <div class="apl-video-modal-panel">
        <button type="button" class="apl-video-modal-close" data-close-video aria-label="Close"><i class="ti ti-x"></i></button>
        <video controls playsinline></video>
    </div>
</div>
@endsection

@push('scripts')
<script>
const aplVideoModal = document.getElementById('apl-video-modal');
const aplVideoPlayer = aplVideoModal?.querySelector('video');

const closeAplVideoModal = () => {
    if (!aplVideoModal || !aplVideoPlayer) return;
    aplVideoPlayer.pause();
    aplVideoPlayer.removeAttribute('src');
    aplVideoPlayer.load();
    aplVideoModal.hidden = true;
};

const openAplVideoModal = (url) => {
    if (!aplVideoModal || !aplVideoPlayer || !url) return;
    aplVideoPlayer.src = url;
    aplVideoPlayer.muted = false;
    aplVideoModal.hidden = false;
    aplVideoPlayer.play().catch(() => {});
};

document.querySelectorAll('.apl-video-play').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openAplVideoModal(btn.dataset.videoUrl || '');
    });
});

aplVideoModal?.querySelectorAll('[data-close-video]').forEach((el) => {
    el.addEventListener('click', closeAplVideoModal);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && aplVideoModal && !aplVideoModal.hidden) {
        closeAplVideoModal();
    }
});

document.querySelectorAll('.apl-menu-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const wrap = btn.closest('.apl-menu-wrap');
        document.querySelectorAll('.apl-menu-wrap.open').forEach(w => { if (w !== wrap) w.classList.remove('open'); });
        wrap.classList.toggle('open');
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.apl-menu-wrap.open').forEach(w => w.classList.remove('open'));
});

document.querySelectorAll('[data-apl-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('.apl-carousel-track');
    const slides = carousel.querySelectorAll('.apl-carousel-slide');
    const prevBtn = carousel.querySelector('.apl-carousel-prev');
    const nextBtn = carousel.querySelector('.apl-carousel-next');
    const countEl = carousel.querySelector('.apl-carousel-count');

    if (!track || slides.length <= 1) return;

    let index = 0;

    const render = () => {
        track.style.transform = `translateX(-${index * 100}%)`;
        if (countEl) countEl.textContent = `${index + 1}/${slides.length}`;
    };

    prevBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        index = (index - 1 + slides.length) % slides.length;
        render();
    });

    nextBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        index = (index + 1) % slides.length;
        render();
    });
});
</script>
@endpush
