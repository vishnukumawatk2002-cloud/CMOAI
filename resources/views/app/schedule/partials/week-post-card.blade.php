@php
    use App\Models\ContentItem;

    /** @var ContentItem $item */
    $platformIcons = $platformIcons ?? [
        'linkedin' => ['icon' => 'ti-brand-linkedin', 'color' => '#0A66C2'],
        'instagram' => ['icon' => 'ti-brand-instagram', 'color' => '#E1306C'],
        'facebook' => ['icon' => 'ti-brand-facebook', 'color' => '#1877F2'],
        'x' => ['icon' => 'ti-brand-x', 'color' => '#111827'],
        'twitter' => ['icon' => 'ti-brand-x', 'color' => '#111827'],
        'youtube' => ['icon' => 'ti-brand-youtube', 'color' => '#FF0000'],
    ];

    $platform = strtolower($item->platform ?? 'instagram');
    $pMeta = $platformIcons[$platform] ?? $platformIcons['instagram'];
    $media = $item->schedule_media ?? [];
    $thumb = $media['thumbnail'] ?? data_get($item->metadata, 'thumbnail_url');
    $videoUrl = $media['video'] ?? data_get($item->metadata, 'video_url');
    $postTypeKey = data_get($item->metadata, 'post_type', ($item->content_type ?? '') === 'reel_script' ? 'reel' : 'image');
    if (($item->content_type ?? '') === 'carousel') {
        $postTypeKey = 'carousel';
    }
    $isVideoPost = $postTypeKey === 'reel' || ($item->content_type ?? '') === 'reel_script';
    $isCarouselPost = $postTypeKey === 'carousel';
    $carouselImages = collect($media['carousel'] ?? data_get($item->metadata, 'carousel_images', []))->filter()->values();
    $imageUrl = $isCarouselPost && $carouselImages->isNotEmpty()
        ? $carouselImages->first()
        : ($isVideoPost ? null : $thumb);
    $hasMedia = ($isVideoPost && filled($videoUrl)) || filled($imageUrl);
    $timeLabel = $item->scheduled_at?->format('g:i A') ?? '';
    $postTypeMeta = match ($postTypeKey) {
        'reel', 'reel_script' => ['label' => 'Reel', 'icon' => 'ti-player-play', 'class' => 'is-reel'],
        'carousel' => ['label' => 'Carousel', 'icon' => 'ti-layout-carousel', 'class' => 'is-carousel'],
        default => ['label' => 'Post', 'icon' => 'ti-photo', 'class' => 'is-post'],
    };
@endphp

<a href="{{ route('app.brand.ai-post-library.show', $item) }}" class="pub-week-card">
    <div class="pub-week-card-top">
        <div class="pub-week-card-time">
            <i class="ti ti-clock"></i>
            <span>{{ $timeLabel }}</span>
        </div>
        <i class="ti {{ $pMeta['icon'] }} pub-week-card-platform" style="color:{{ $pMeta['color'] }}"></i>
    </div>

    <div class="pub-week-card-media {{ $hasMedia ? '' : 'is-empty' }} {{ $postTypeMeta['class'] }}">
        @if ($isVideoPost && filled($videoUrl))
            <video class="pub-week-media-video" src="{{ $videoUrl }}" muted playsinline preload="metadata"></video>
            <span class="pub-week-play pub-week-play--reel"><i class="ti ti-player-play"></i></span>
        @elseif (filled($imageUrl))
            <img class="pub-week-media-image" src="{{ $imageUrl }}" alt="">
        @else
            <span class="pub-week-play pub-week-play--empty">
                <i class="ti {{ $postTypeMeta['icon'] }}"></i>
            </span>
        @endif
        <span class="pub-week-type">
            <i class="ti {{ $postTypeMeta['icon'] }}"></i>
            {{ $postTypeMeta['label'] }}
        </span>
    </div>
</a>
