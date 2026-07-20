@php
    $platformIcons = [
        'linkedin' => ['icon' => 'ti-brand-linkedin', 'color' => '#0A66C2'],
        'instagram' => ['icon' => 'ti-brand-instagram', 'color' => '#E1306C'],
        'facebook' => ['icon' => 'ti-brand-facebook', 'color' => '#1877F2'],
        'x' => ['icon' => 'ti-brand-x', 'color' => '#111827'],
        'youtube' => ['icon' => 'ti-brand-youtube', 'color' => '#FF0000'],
        'snapchat' => ['icon' => 'ti-brand-snapchat', 'color' => '#FFFC00'],
    ];
    $platform = strtolower($item->platform ?? 'instagram');
    $pMeta = $platformIcons[$platform] ?? $platformIcons['instagram'];
    $thumb = data_get($item->metadata, 'thumbnail_url');
    $postTypeKey = data_get($item->metadata, 'post_type', ($item->content_type ?? '') === 'reel_script' ? 'reel' : 'image');
    $isVideoPost = $postTypeKey === 'reel' || ($item->content_type ?? '') === 'reel_script';
    $isCarouselPost = $postTypeKey === 'carousel' || ($item->content_type ?? '') === 'carousel';
    $carouselImages = $item->carousel_images ?? [];
    $videoUrl = data_get($item->metadata, 'video_url') ?? ($isVideoPost ? $thumb : null);
    $sourceTag = data_get($item->metadata, 'planning_source', 'Manual');
    $statusLabels = [
        'draft' => ['label' => 'Draft', 'class' => 'draft'],
        'approved' => ['label' => 'Approved', 'class' => 'approved'],
        'scheduled' => ['label' => 'Scheduled', 'class' => 'scheduled'],
        'published' => ['label' => 'Published', 'class' => 'published'],
        'failed' => ['label' => 'Failed', 'class' => 'failed'],
    ];
    $status = $statusLabels[$item->status] ?? $statusLabels['draft'];
    $postTypeLabels = [
        'image' => 'Post',
        'reel' => 'Reel script',
        'carousel' => 'Carousel',
        'post' => 'Post',
        'reel_script' => 'Reel script',
    ];
    $fromPlanning = data_get($item->metadata, 'post_type');
    $postTypeLabel = ($fromPlanning && isset($postTypeLabels[$fromPlanning]))
        ? $postTypeLabels[$fromPlanning]
        : ($postTypeLabels[$item->content_type ?? 'post'] ?? ucfirst(str_replace('_', ' ', $item->content_type ?? 'post')));
    $platformLabel = match ($platform) {
        'x' => 'X',
        'youtube' => 'YouTube',
        default => ucfirst($platform),
    };
    $publishAccount = $item->publish_account ?? [];
    $youtubeChannelId = $publishAccount['external_id'] ?? null;
    $youtubePostsUrl = filled($youtubeChannelId) && ! str_starts_with((string) $youtubeChannelId, 'demo-')
        ? 'https://www.youtube.com/channel/'.$youtubeChannelId.'/posts'
        : 'https://studio.youtube.com';
    $youtubeCanApiPublish = $isVideoPost; // reel/Shorts or video only via API
    $formattedBody = e(trim((string) $item->body));
    $formattedBody = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $formattedBody) ?? $formattedBody;
    $formattedBody = preg_replace('/\s+(?=(?:\d+\.|[•▪])\s)/u', "\n", $formattedBody) ?? $formattedBody;
    $formattedBody = nl2br($formattedBody, false);
@endphp

@extends('layouts.app')

@section('title', 'Preview post — '.$brand->name)
@section('pageTitle', 'Preview post')

@section('topbarExtra')
    <a href="{{ route('app.brand.ai-post-library') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Back to library</a>
    @if($item->status === 'published' && filled($item->external_post_url))
        <a href="{{ $item->external_post_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><i class="ti ti-external-link"></i> View on {{ $platformLabel }}</a>
    @elseif(strtolower($item->platform ?? '') === 'facebook' && $item->status !== 'published')
        <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-green btn-sm"><i class="ti ti-send"></i> Publish to Facebook</button>
        </form>
    @elseif(strtolower($item->platform ?? '') === 'instagram' && $item->status !== 'published')
        <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-green btn-sm"><i class="ti ti-send"></i> Publish to Instagram</button>
        </form>
    @elseif(strtolower($item->platform ?? '') === 'linkedin' && $item->status !== 'published')
        <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-green btn-sm"><i class="ti ti-send"></i> Publish to LinkedIn</button>
        </form>
    @elseif(strtolower($item->platform ?? '') === 'x' && $item->status !== 'published')
        <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-green btn-sm"><i class="ti ti-send"></i> Publish to X</button>
        </form>
    @elseif(strtolower($item->platform ?? '') === 'youtube' && $item->status !== 'published')
        @if($youtubeCanApiPublish)
            <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-green btn-sm"><i class="ti ti-send"></i> Publish YouTube Short</button>
            </form>
        @else
            <button type="button" class="btn btn-ghost btn-sm" id="yt-copy-caption" data-caption="{{ e($item->body ?? '') }}"><i class="ti ti-copy"></i> Copy caption</button>
            @if($isCarouselPost && count($carouselImages))
                <a href="{{ $carouselImages[0] }}" download class="btn btn-ghost btn-sm" target="_blank" rel="noopener"><i class="ti ti-download"></i> Download image</a>
            @elseif(filled($thumb))
                <a href="{{ $thumb }}" download class="btn btn-ghost btn-sm" target="_blank" rel="noopener"><i class="ti ti-download"></i> Download image</a>
            @endif
            <a href="{{ $youtubePostsUrl }}" target="_blank" rel="noopener" class="btn btn-green btn-sm"><i class="ti ti-brand-youtube"></i> Post on YouTube Posts</a>
        @endif
    @elseif(strtolower($item->platform ?? '') === 'snapchat' && $item->status !== 'published')
        <form method="POST" action="{{ route('app.brand.ai-post-library.publish', $item) }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-green btn-sm"><i class="ti ti-send"></i> Publish to Snapchat</button>
        </form>
    @endif
    <a href="{{ route('app.brand.ai-post-library.edit', $item) }}" class="btn btn-green btn-sm"><i class="ti ti-pencil"></i> Edit</a>
@endsection

@section('content')
@if(strtolower($item->platform ?? '') === 'youtube' && $item->status !== 'published' && ! $youtubeCanApiPublish)
    <div class="sa-notice" style="margin-bottom:16px;background:#FEF3C7;border:1px solid #F59E0B;color:#92400E">
        <i class="ti ti-info-circle"></i>
        <span>
            Aap jo jagah chahte ho (<strong>YouTube → Posts</strong> tab, jahan “hello” dikhta hai) — wahan Google <strong>API se auto-post allow nahi karti</strong>.
            Isliye CMO AI se caption/image seedha wahan nahi jayegi.
            Easy way: <strong>Copy caption</strong> → <strong>Download image</strong> → <strong>Post on YouTube Posts</strong> → Image choose → paste caption → Post.
        </span>
    </div>
@endif
<div class="apl-preview-wrap">
    <article class="apl-card apl-card-preview">
        <div class="apl-head">
            @include('app.brand.ai-post-library.partials.card-channel-head', [
                'item' => $item,
                'platform' => $platform,
                'pMeta' => $pMeta,
                'postTypeLabel' => $postTypeLabel,
                'displayTime' => $item->created_at->diffForHumans(),
                'timeIcon' => 'ti-clock',
                'publishAccount' => $publishAccount,
            ])
            <span class="apl-status apl-status-{{ $status['class'] }}">
                {{ strtoupper($status['label']) }}
            </span>
        </div>

        <div class="apl-body apl-body-preview">
            <div class="apl-body-main">
                <h3 class="apl-title">{{ $item->title ?? 'Untitled post' }}</h3>
                <span class="apl-source-tag">{{ $sourceTag }}</span>
                <div class="apl-desc apl-desc-full apl-content-copy">{!! $formattedBody !!}</div>
            </div>
            @if($isCarouselPost && count($carouselImages))
                <div class="apl-thumb apl-thumb-large apl-thumb--carousel">
                    @include('app.brand.ai-post-library.partials.carousel-slider', ['images' => $carouselImages, 'size' => 'large'])
                </div>
            @elseif($isVideoPost && $videoUrl)
                <div class="apl-thumb apl-thumb-large apl-thumb--video">
                    <div class="apl-thumb-video apl-thumb-video-large">
                        <video src="{{ $videoUrl }}" muted playsinline preload="metadata"></video>
                        <button type="button" class="apl-thumb-play apl-thumb-play-lg apl-video-play" data-video-url="{{ $videoUrl }}" title="Play video">
                            <i class="ti ti-player-play"></i>
                        </button>
                    </div>
                </div>
            @elseif($thumb)
                <div class="apl-thumb apl-thumb-large">
                    <img src="{{ $thumb }}" alt="">
                </div>
            @endif
        </div>
    </article>
</div>

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

document.querySelectorAll('.apl-video-play').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const url = btn.dataset.videoUrl || '';
        if (!aplVideoModal || !aplVideoPlayer || !url) return;
        aplVideoPlayer.src = url;
        aplVideoPlayer.muted = false;
        aplVideoModal.hidden = false;
        aplVideoPlayer.play().catch(() => {});
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
        index = (index - 1 + slides.length) % slides.length;
        render();
    });

    nextBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        index = (index + 1) % slides.length;
        render();
    });
});

document.getElementById('yt-copy-caption')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const text = btn.getAttribute('data-caption') || '';
    try {
        await navigator.clipboard.writeText(text);
        const old = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i> Copied';
        setTimeout(() => { btn.innerHTML = old; }, 1500);
    } catch (_) {
        alert('Copy failed. Caption select karke Ctrl+C karo.');
    }
});
</script>
@endpush
