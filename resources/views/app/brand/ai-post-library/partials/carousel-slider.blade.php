@php
    $slides = collect($images ?? [])->filter()->values();
    $sizeClass = ($size ?? 'thumb') === 'large' ? 'apl-carousel--large' : '';
@endphp

@if($slides->isNotEmpty())
    <div class="apl-carousel {{ $sizeClass }}" data-apl-carousel>
        <div class="apl-carousel-track">
            @foreach($slides as $slideUrl)
                <div class="apl-carousel-slide">
                    <img src="{{ $slideUrl }}" alt="">
                </div>
            @endforeach
        </div>
        @if($slides->count() > 1)
            <button type="button" class="apl-carousel-nav apl-carousel-prev" aria-label="Previous slide"><i class="ti ti-chevron-left"></i></button>
            <button type="button" class="apl-carousel-nav apl-carousel-next" aria-label="Next slide"><i class="ti ti-chevron-right"></i></button>
            <span class="apl-carousel-count">1/{{ $slides->count() }}</span>
        @endif
    </div>
@endif
