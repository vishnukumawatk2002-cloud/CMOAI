<style>
    .layout--brand-workspace .clib-grid {
    grid-template-columns: repeat({{ max(1, count($categories ?? [])) }},minmax(0,1fr));
}

.devtab {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 16px;
    padding: 10px;
    background: none;
    border: none;
    border-radius: none;
}

.devbtn{
   
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: var(--r);
    font-family: 'Inter',sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .18s;
    line-height: 1;
    background-color: #0dc9a0 !important;
    outline: none;
    color: #fff !important;
    text-decoration: none;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

</style>
@php
    $libraryRouteName = request()->route()->getName();
    $showLibrarySourceTabs = $libraryRouteName === 'app.brand.content-library';
    $isMixedPlanning = ($planningMixedContent ?? false) || (($showPlanningSelect ?? false) && ($tab ?? '') === 'planning');
    $canAccessReels = $canAccessReels ?? true;
    $aiFeatureLocked = $aiFeatureLocked ?? ! ($canAccessGeneratedAiLibrary ?? true);
@endphp
<div class="clib-page">
    @if ($showLibrarySourceTabs)
    <div class="clib-tabs">
        <a href="{{ route($routeName, ['tab' => 'ai']) }}"
           class="clib-tab {{ $tab === 'ai' ? 'active' : '' }}">
            <i class="ti ti-sparkles"></i> Generated AI Content
        </a>
        <a href="{{ route($routeName, ['tab' => 'manual']) }}"
           class="clib-tab {{ $tab === 'manual' ? 'active' : '' }}">
            <i class="ti ti-upload"></i> Manually uploaded Content
        </a>

        @if ($tab === 'manual')
            <button type="button" class="btn btn-green btn-sm clib-upload-btn" data-open-clib-upload-modal>
                <i class="ti ti-upload"></i> Upload content
            </button>
        @elseif (! $aiFeatureLocked)
            <div class="clib-tab-meta">
                @if ($kbReady && in_array($aiProvider ?? 'local', ['openrouter', 'bluesminds', 'groq', 'openai', 'gemini'], true))
                    <span class="bds-status-pill groq-live"><i class="ti ti-bolt"></i> From Brand Knowledge Base</span>
                @elseif ($kbReady)
                    <span class="bds-status-pill count"><i class="ti ti-brain"></i> From Brand Knowledge Base</span>
                @else
                    <span class="bds-status-pill learning"><i class="ti ti-loader"></i> Train knowledge base first</span>
                @endif
            </div>
        @endif
    </div>
    @endif

    @if ($showLibrarySourceTabs && $tab === 'ai' && $aiFeatureLocked)
        @include('app.brand.partials.plan-upgrade')
    @else
    @if ($showPlanningSelect ?? false)
    @php
        $connectedChannels = collect($connectedChannels ?? []);
        $platformMeta = [
            'linkedin' => ['icon' => 'ti-brand-linkedin', 'label' => 'LinkedIn'],
            'instagram' => ['icon' => 'ti-brand-instagram', 'label' => 'Instagram'],
            'x' => ['icon' => 'ti-brand-x', 'label' => 'X'],
            'facebook' => ['icon' => 'ti-brand-facebook', 'label' => 'Facebook'],
            'youtube' => ['icon' => 'ti-brand-youtube', 'label' => 'Youtube'],
        ];
        $platformCounts = $connectedChannels->countBy('platform');
    @endphp
    <div class="pp-composer card">
    <div class="clib-tabs devtab">
       

       

        @if ($showPlanningSelect ?? false)
            <form method="POST" action="{{ route($routeName.'.save') }}" id="clib-plan-save-form" class="clib-plan-save-form">
                @csrf
                <input type="hidden" name="tab" value="{{ $isMixedPlanning ? 'mixed' : $tab }}">
                <input type="hidden" name="post_type" id="clib-plan-post-type" value="image">
                <div id="clib-plan-selected-inputs"></div>
                <button type="submit" class="btn btn-green btn-sm devbtn" id="clib-save-plan-btn" disabled>
                    <i class="ti ti-device-floppy"></i> Save
                </button>
            </form>
        @endif
    </div>
        <div class="pp-section">
            <div class="pp-section-head">
                <div>
                    <label class="pp-label">Channel Social Account</label>
                    <p class="pp-sub">Choose one or more connected profiles for this post.</p>
                </div>
                <a href="{{ route('app.brand.social-accounts') }}" class="pp-link">Manage in social accounts <i class="ti ti-external-link"></i></a>
            </div>

            <div class="pp-channel-panel" id="pp-channel-tags-panel">
                <div class="pp-channel-tags" id="pp-channel-tags">
                    <span class="pp-channel-empty" id="pp-channel-empty">No accounts selected</span>
                </div>
                <button type="button" class="pp-channel-toggle" id="pp-channel-toggle" aria-label="Toggle channel list" aria-expanded="true">
                    <i class="ti ti-chevron-up"></i>
                </button>
            </div>

            <div class="pp-channel-body" id="pp-channel-body">
                <div class="pp-channel-search-row">
                    <div class="pp-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="pp-channel-search" placeholder="Search accounts" autocomplete="off">
                    </div>
                    <span class="pp-selected-pill" id="pp-selected-count">0 selected</span>
                    <label class="pp-select-all" title="Select all visible">
                        <input type="checkbox" id="pp-select-all">
                    </label>
                </div>

                @if ($connectedChannels->isEmpty())
                    <div class="pp-channel-empty-state">
                        <i class="ti ti-plug-connected-x"></i>
                        <p>No social accounts connected yet.</p>
                        <a href="{{ route('app.brand.social-accounts') }}" class="btn btn-green btn-sm">
                            <i class="ti ti-plus"></i> Connect a channel
                        </a>
                    </div>
                @else
                    <div class="pp-channel-filters" id="pp-channel-filters">
                        <button type="button" class="pp-channel-filter is-active" data-platform-filter="all">
                            All <span class="pp-filter-count">{{ $connectedChannels->count() }}</span>
                        </button>
                        @foreach ($platformCounts as $platform => $count)
                            @php $meta = $platformMeta[$platform] ?? ['icon' => 'ti-share', 'label' => ucfirst($platform)]; @endphp
                            <button type="button" class="pp-channel-filter" data-platform-filter="{{ $platform }}">
                                <i class="ti {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                                <span class="pp-filter-count">{{ $count }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="pp-channel-list" id="pp-channel-list">
                        @foreach ($connectedChannels as $channel)
                            @php $meta = $platformMeta[$channel['platform']] ?? ['icon' => 'ti-share', 'label' => ucfirst($channel['platform'])]; @endphp
                            <label class="pp-channel-row"
                                   data-channel-id="{{ $channel['id'] }}"
                                   data-platform="{{ $channel['platform'] }}"
                                   data-name="{{ strtolower($channel['name']) }}"
                                   data-subtitle="{{ strtolower($channel['subtitle'] ?? '') }}">
                                @if (! empty($channel['profile_image_url']))
                                    <span class="pp-channel-row-avatar pp-channel-row-avatar--img" style="background-image:url('{{ $channel['profile_image_url'] }}')" title="{{ $channel['name'] }}" role="img" aria-label="{{ $channel['name'] }}"></span>
                                @else
                                    <span class="pp-channel-row-avatar" style="{{ $channel['avatar_style'] }}">{{ $channel['initials'] }}</span>
                                @endif
                                <span class="pp-channel-row-info">
                                    <span class="pp-channel-row-name">{{ $channel['name'] }}</span>
                                    <span class="pp-channel-row-sub">
                                        <i class="ti {{ $meta['icon'] }}"></i>
                                        {{ $meta['label'] }} · {{ $channel['subtitle'] }}
                                    </span>
                                </span>
                                <input type="checkbox" class="pp-channel-checkbox" value="{{ $channel['id'] }}"
                                       data-platform="{{ $channel['platform'] }}"
                                       data-name="{{ $channel['name'] }}"
                                       data-initials="{{ $channel['initials'] }}"
                                       data-avatar-style="{{ $channel['avatar_style'] }}"
                                       data-avatar-url="{{ $channel['profile_image_url'] ?? '' }}">
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="pp-section">
            <label class="pp-label">Create post type</label>
            <div class="pp-post-types">
                <button type="button" class="pp-post-type-btn active" data-post-type="image">
                    <i class="ti ti-photo"></i> Create Image Post
                </button>
                <button type="button" class="pp-post-type-btn" data-post-type="reel">
                    <i class="ti ti-player-play"></i> Create Reels Short
                </button>
                <button type="button" class="pp-post-type-btn" data-post-type="carousel">
                    <i class="ti ti-layout-grid"></i> Create Carousel Post
                </button>
            </div>
            <p class="pp-hint" id="pp-type-hint">Select one caption and one image below, then click Save.</p>
        </div>
    </div>
    @endif

    @if ($tab === 'ai' && ! $kbReady)
        <div class="clib-empty-banner">
            <i class="ti ti-brain"></i>
            <div>
                <strong>Brand Knowledge Base is not trained yet.</strong>
                <p>Add brand data sources and train the KB â€” AI content will appear here automatically.</p>
            </div>
            <a href="{{ route('app.brand.data-sources') }}" class="btn btn-ghost btn-sm">Go to data sources</a>
        </div>
    @endif

    <div class="clib-grid-wrap">
    <div class="clib-grid">
        @foreach ($categories as $category)
            <div class="clib-column card" data-clib-column="{{ $category['key'] }}">
                <div class="clib-column-head">
                    <div class="clib-column-icon" style="background:{{ $category['bg'] }}">
                        <i class="ti {{ $category['icon'] }}" style="color:{{ $category['color'] }}"></i>
                    </div>
                    <div class="clib-column-title">{{ $category['title'] }}</div>
                    <span class="bds-count-badge">{{ count($category['items']) }}</span>
                </div>

                <div class="clib-item-list">
                    @forelse ($category['items'] as $item)
                        @php
                            $isVisualReel = $category['key'] === 'reel';
                            $isManualItem = ($item['source'] ?? '') === 'manual';
                            $isAiItem = ! empty($item['content_item_id']) || in_array($item['source'] ?? '', ['generated', 'knowledge_base'], true);
                            $hideTags = true;
                            $hasGeneratedVisual = ! empty($item['image_url'])
                                || ! empty($item['caption_image_url'])
                                || ! empty($item['reel_preview'])
                                || collect($item['preview_images'] ?? [])->contains(fn ($preview) => ! empty($preview['url']));
                            $hideBody = $hideTags
                                && $category['key'] !== 'caption'
                                && (($item['source'] ?? '') !== 'generated' || $hasGeneratedVisual);
                            $planThumb = $item['image_url']
                                ?? $item['caption_image_url']
                                ?? ($item['video_url'] ?? null)
                                ?? (optional(collect($item['preview_images'] ?? [])->first(fn ($p) => ! empty($p['url'])))['url'] ?? null);
                            $planCarouselImages = collect($item['preview_images'] ?? [])
                                ->pluck('url')
                                ->filter()
                                ->values()
                                ->all();
                            $showAiActions = $isMixedPlanning
                                ? ! empty($item['content_item_id'])
                                : ($tab === 'ai' && ! empty($item['content_item_id']));
                            $showManualActions = $isMixedPlanning
                                ? ($isManualItem && ! empty($item['manual_key']))
                                : ($tab === 'manual' && ! empty($item['manual_key']));
                            $showManualDataAttrs = $showManualActions;
                        @endphp
                        <div class="clib-item-card {{ $isVisualReel ? 'clib-item-card-reel' : '' }}"
                             @if ($showPlanningSelect ?? false)
                             data-plan-category="{{ $category['key'] }}"
                             data-plan-title="{{ $item['title'] ?? '' }}"
                             data-plan-body="{{ $item['text'] ?? '' }}"
                             data-plan-manual-type="{{ $item['manual_type'] ?? '' }}"
                             data-plan-manual-key="{{ $item['manual_key'] ?? '' }}"
                             data-plan-thumbnail="{{ $planThumb ?? '' }}"
                             data-plan-video-url="{{ $item['video_url'] ?? '' }}"
                             data-plan-carousel-images='@json($planCarouselImages)'
                             @endif
                             @if ($showManualDataAttrs)
                             data-manual-type="{{ $item['manual_type'] }}"
                             data-manual-key="{{ $item['manual_key'] }}"
                             data-caption='@json($item['text'] ?? '')'
                             data-title="{{ $item['title'] ?? '' }}"
                             @endif>
                            <div class="clib-item-head {{ $isVisualReel ? 'clib-item-head-minimal' : '' }}">
                                @if ($showPlanningSelect ?? false)
                                    <label class="clib-plan-check" title="Select for plan">
                                        <input type="checkbox" class="clib-plan-checkbox" data-plan-checkbox data-plan-category="{{ $category['key'] }}" disabled>
                                    </label>
                                @endif
                                <div class="clib-item-number">{{ $item['number'] }}</div>
                                @if ($showAiActions)
                                    <div class="clib-item-actions">
                                        @if ($category['key'] === 'caption')
                                            <button type="button" class="clib-action-btn clib-copy-btn" title="Copy">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                        @endif
                                        <form method="POST"
                                              action="{{ route($routeName.'.destroy-ai') }}"
                                              class="clib-delete-form"
                                              onsubmit="return confirm('Delete this content?');">
                                            @csrf
                                            <input type="hidden" name="content_item_id" value="{{ $item['content_item_id'] }}">
                                            <button type="submit" class="clib-action-btn clib-action-delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                @if ($showManualActions)
                                    <div class="clib-item-actions">
                                        <button type="button"
                                                class="clib-action-btn"
                                                title="Edit"
                                                data-edit-manual
                                                data-manual-type="{{ $item['manual_type'] }}"
                                                data-manual-key="{{ $item['manual_key'] }}"
                                                data-caption='@json($item['text'] ?? '')'
                                                data-title="{{ $item['title'] ?? '' }}">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        <form method="POST"
                                              action="{{ route($routeName.'.destroy-manual') }}"
                                              class="clib-delete-form"
                                              onsubmit="return confirm('Delete this content?');">
                                            @csrf
                                            <input type="hidden" name="manual_type" value="{{ $item['manual_type'] }}">
                                            <input type="hidden" name="manual_key" value="{{ $item['manual_key'] }}">
                                            <button type="submit" class="clib-action-btn clib-action-delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            @unless ($isVisualReel)
                                <div class="clib-item-title">{{ $item['title'] }}</div>
                            @endunless

                            @if (! empty($item['reel_preview']))
                                <div class="clib-reel-preview {{ ! empty($item['video_url']) ? 'clib-reel-playable' : '' }}"
                                     @if (! empty($item['video_url']))
                                     data-video-url="{{ $item['video_url'] }}"
                                     data-video-title="{{ $item['title'] ?? 'Reel video' }}"
                                     title="Click to play video"
                                     role="button"
                                     tabindex="0"
                                     @endif>
                                    @if (! empty($item['video_url']))
                                        <video src="{{ $item['video_url'] }}" muted playsinline preload="metadata"></video>
                                    @endif
                                    <span class="clib-reel-play"><i class="ti ti-player-play"></i></span>
                                </div>
                            @elseif (! empty($item['caption_image_url']))
                                @php $captionImageEditable = $showManualActions; @endphp
                                <div class="clib-caption-image {{ $captionImageEditable ? 'clib-preview-edit' : 'clib-image-preview' }}"
                                     @unless ($captionImageEditable)
                                     data-image-url="{{ $item['caption_image_url'] }}"
                                     data-image-title="{{ $item['title'] ?? 'Caption image' }}"
                                     role="button"
                                     tabindex="0"
                                     title="Click to view image"
                                     @else
                                     title="Click to update image"
                                     @endunless>
                                    <img src="{{ $item['caption_image_url'] }}" alt="Caption image">
                                    @if ($captionImageEditable)
                                        <span class="clib-update-overlay"><i class="ti ti-pencil"></i> Update</span>
                                    @endif
                                </div>
                            @elseif (! empty($item['image_url']))
                                @php $singleImageEditable = $showManualActions; @endphp
                                <div class="clib-single-image {{ $singleImageEditable ? 'clib-preview-edit' : 'clib-image-preview' }}"
                                     @unless ($singleImageEditable)
                                     data-image-url="{{ $item['image_url'] }}"
                                     data-image-title="{{ $item['title'] ?? 'Image' }}"
                                     role="button"
                                     tabindex="0"
                                     title="Click to view image"
                                     @else
                                     title="Click to update image"
                                     @endunless>
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] ?? 'Image' }}">
                                    @if ($singleImageEditable)
                                        <span class="clib-update-overlay"><i class="ti ti-pencil"></i> Update</span>
                                    @endif
                                </div>
                            @elseif (! empty($item['preview_images']))
                                @php
                                    $carouselUrls = collect($item['preview_images'])->pluck('url')->filter()->values()->all();
                                    $isCarousel = $showManualActions && ($item['manual_type'] ?? '') === 'carousel';
                                    $isManualEditable = $showManualActions;
                                    $isCarouselPreview = count($carouselUrls) > 0 && ! $isManualEditable;
                                @endphp
                                <div class="clib-image-grid {{ $isCarouselPreview ? 'clib-carousel-preview' : '' }}"
                                     @if ($isCarouselPreview)
                                     data-carousel-title="{{ $item['title'] ?? 'Carousel images' }}"
                                     data-carousel-images='@json($carouselUrls)'
                                     role="button"
                                     tabindex="0"
                                     title="Click to view carousel"
                                     @endif>
                                    @foreach ($item['preview_images'] as $preview)
                                        @php
                                            $isManualEditable = $showManualActions;
                                        @endphp
                                        <div class="clib-image-cell {{ empty($preview['url']) ? 'is-placeholder' : '' }} {{ $isManualEditable ? ($isCarousel ? 'clib-carousel-slot' : 'clib-preview-edit') : '' }}"
                                             @if ($isCarousel)
                                             data-slot="{{ $preview['slot'] ?? $loop->index }}"
                                             @endif
                                             title="{{ $isManualEditable ? (empty($preview['url']) ? ($isCarousel ? 'Click to add slide' : 'Click to update image') : ($isCarousel ? 'Click to update slide' : 'Click to update image')) : '' }}">
                                            @if (! empty($preview['url']))
                                                <img src="{{ $preview['url'] }}" alt="{{ $preview['label'] }}">
                                            @else
                                                <span>{{ $preview['label'] }}</span>
                                            @endif
                                            @if ($isManualEditable)
                                                <span class="clib-update-overlay"><i class="ti ti-pencil"></i> {{ empty($preview['url']) ? ($isCarousel ? 'Add' : 'Update') : 'Update' }}</span>
                                            @endif
                                            @if ($isCarousel && ! empty($preview['asset_id']))
                                                <form method="POST"
                                                      action="{{ route($routeName.'.destroy-carousel-slot') }}"
                                                      class="clib-slot-delete-form"
                                                      onsubmit="return confirm('Delete this slide?');">
                                                    @csrf
                                                    <input type="hidden" name="asset_id" value="{{ $preview['asset_id'] }}">
                                                    <button type="submit" class="clib-slot-delete-btn" title="Delete slide" onclick="event.stopPropagation();">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($showManualActions && ! empty($item['is_image']))
                                <div class="clib-item-preview clib-item-preview-image">
                                    <i class="ti ti-photo"></i>
                                </div>
                            @elseif ($showManualActions && ! empty($item['is_video']))
                                <div class="clib-item-preview clib-item-preview-video">
                                    <i class="ti ti-player-play"></i>
                                </div>
                            @endif

                            @if (! $hideBody)
                                <div class="clib-item-body {{ $category['key'] === 'caption' ? 'clib-caption-preview' : '' }}"
                                     @if ($category['key'] === 'caption')
                                     data-caption-title="{{ $item['title'] ?? 'Caption' }}"
                                     role="button"
                                     tabindex="0"
                                     title="Click to view full caption"
                                     @endif>{{ $item['text'] }}</div>
                            @endif

                            <div class="clib-item-meta">
                                @if (($item['source'] ?? '') === 'manual' || (! empty($item['manual_key']) && empty($item['content_item_id'])))
                                    <span class="clib-source-badge manual">Uploaded</span>
                                @elseif (($item['source'] ?? '') === 'knowledge_base')
                                    <span class="clib-source-badge kb">KB AI</span>
                                @else
                                    <span class="clib-source-badge generated">Generated</span>
                                @endif

                                @if (! $hideTags)
                                    {{-- Hashtags — hidden per request
                                    @foreach ($item['tags'] ?? [] as $tag)
                                        <span class="bcs-prompt-tag">{{ is_string($tag) ? '#'.str_replace(' ', '', $tag) : $tag }}</span>
                                    @endforeach
                                    --}}
                                @endif

                                @if (! empty($item['content_item_id']))
                                    <a href="{{ route('app.content.edit', $item['content_item_id']) }}" class="clib-item-link">Edit post</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="clib-column-empty">
                            @if ($isMixedPlanning)
                                <i class="ti ti-layout-grid"></i>
                                <p>No content yet</p>
                                <p class="clib-column-empty-sub">Upload in Content Library or generate AI content.</p>
                            @elseif ($tab === 'manual')
                                <i class="ti ti-upload"></i>
                                <p>No uploads yet</p>
                            @else
                                <i class="ti ti-sparkles"></i>
                                <p>No AI content yet</p>
                                <p class="clib-column-empty-sub">Select prompts in Brand Content Suggestions and click Generated AI.</p>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
    </div>
    @endif
</div>

<div class="modal-overlay clib-video-modal" id="clib-video-modal" aria-hidden="true">
    <div class="cmo-modal clib-video-modal-card" role="dialog" aria-modal="true" aria-labelledby="clib-video-modal-title">
        <div class="modal-head">
            <div>
                <h2 id="clib-video-modal-title">Reel preview</h2>
                <p id="clib-video-modal-subtitle">Generated reel video</p>
            </div>
            <button type="button" class="close-btn" data-close-clib-video-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body clib-video-modal-body">
            <video id="clib-video-modal-player" controls playsinline preload="metadata"></video>
        </div>
    </div>
</div>

<div class="modal-overlay clib-image-modal" id="clib-image-modal" aria-hidden="true">
    <div class="cmo-modal clib-image-modal-card" role="dialog" aria-modal="true" aria-labelledby="clib-image-modal-title">
        <div class="modal-head">
            <div>
                <h2 id="clib-image-modal-title">Image preview</h2>
                <p id="clib-image-modal-subtitle">Generated image</p>
            </div>
            <button type="button" class="close-btn" data-close-clib-image-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body clib-image-modal-body">
            <img id="clib-image-modal-img" src="" alt="Preview image">
        </div>
    </div>
</div>

<div class="modal-overlay clib-carousel-modal" id="clib-carousel-modal" aria-hidden="true">
    <div class="cmo-modal clib-carousel-modal-card" role="dialog" aria-modal="true" aria-labelledby="clib-carousel-modal-title">
        <div class="modal-head">
            <div>
                <h2 id="clib-carousel-modal-title">Carousel preview</h2>
                <p id="clib-carousel-modal-subtitle">Swipe through carousel slides</p>
            </div>
            <button type="button" class="close-btn" data-close-clib-carousel-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body clib-carousel-modal-body">
            <button type="button" class="clib-carousel-nav clib-carousel-prev" id="clib-carousel-prev" aria-label="Previous slide">
                <i class="ti ti-chevron-left"></i>
            </button>
            <div class="clib-carousel-stage">
                <img id="clib-carousel-modal-img" src="" alt="Carousel slide">
            </div>
            <button type="button" class="clib-carousel-nav clib-carousel-next" id="clib-carousel-next" aria-label="Next slide">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>
        <div class="clib-carousel-foot">
            <span id="clib-carousel-counter">1 / 1</span>
        </div>
    </div>
</div>

<div class="modal-overlay clib-caption-modal" id="clib-caption-modal" aria-hidden="true">
    <div class="cmo-modal clib-caption-modal-card" role="dialog" aria-modal="true" aria-labelledby="clib-caption-modal-title">
        <div class="modal-head">
            <div>
                <h2 id="clib-caption-modal-title">Caption preview</h2>
                <p id="clib-caption-modal-subtitle">Full caption text</p>
            </div>
            <button type="button" class="close-btn" data-close-clib-caption-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
            <div class="clib-caption-modal-text" id="clib-caption-modal-text"></div>
        </div>
        <div class="modal-foot" style="justify-content:flex-end;gap:8px">
            <button type="button" class="btn btn-ghost" data-close-clib-caption-modal>Close</button>
            <button type="button" class="btn btn-green btn-sm" id="clib-caption-modal-copy"><i class="ti ti-copy"></i> Copy caption</button>
        </div>
    </div>
</div>

@if ($tab === 'manual' || ($isMixedPlanning ?? false))
@push('modals')
<div class="modal-overlay" id="clib-upload-modal" aria-hidden="true">
    <div class="cmo-modal clib-upload-modal" role="dialog" aria-modal="true" aria-labelledby="clib-upload-title">
        <div class="modal-head">
            <div>
                <h2 id="clib-upload-title">Upload content</h2>
                <p>Add captions, images, reels, or carousel slides to your library.</p>
            </div>
            <button type="button" class="close-btn" data-close-clib-upload-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <form method="POST" action="{{ route($routeName.'.store') }}" enctype="multipart/form-data" id="clib-upload-form">
            @csrf
            <input type="hidden" name="library_type" id="clib-library-type" value="caption">

            <div class="modal-body">
                <div class="sec-h">Content type</div>
                <div class="type-grid clib-type-grid">
                    @foreach([
                        'caption' => ['ti-align-left', 'Caption'],
                        'image' => ['ti-photo', 'Image'],
                        'reel' => ['ti-player-play', 'Reels video'],
                        'carousel' => ['ti-layout-grid', 'Carousel'],
                    ] as $type => [$icon, $label])
                        <button type="button" class="type-card clib-type-card {{ $loop->first ? 'active' : '' }}" data-library-type="{{ $type }}">
                            <i class="ti {{ $icon }}" style="color:var(--purple2)"></i>
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="clib-upload-panel" data-panel="caption">
                    <div class="sec-h" style="margin-top:14px">Caption + Image posts</div>
                    <p class="clib-upload-hint">Add caption and image together â€” both will save in one post.</p>
                    <div class="clib-multi-list" id="clib-captions-list">
                        <div class="clib-pair-row">
                            <div class="clib-pair-fields">
                                <textarea name="captions[]" rows="3" placeholder="Write your captionâ€¦" class="clib-upload-textarea"></textarea>
                                <label class="clib-pair-file">
                                    <input type="file" name="caption_images[]" accept="image/*" class="clib-upload-file-input">
                                    <span><i class="ti ti-photo"></i> Add image</span>
                                </label>
                            </div>
                            <button type="button" class="clib-row-remove" title="Remove" hidden><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm clib-add-row" data-add="caption"><i class="ti ti-plus"></i> Add caption + image</button>
                </div>

                <div class="clib-upload-panel" data-panel="image" hidden>
                    <div class="sec-h" style="margin-top:14px">Images</div>
                    <div class="clib-multi-list" id="clib-images-list">
                        <div class="clib-multi-row">
                            <input type="file" name="images[]" accept="image/*" class="clib-upload-file">
                            <button type="button" class="clib-row-remove" title="Remove" hidden><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm clib-add-row" data-add="image"><i class="ti ti-plus"></i> Add image</button>
                </div>

                <div class="clib-upload-panel" data-panel="reel" hidden>
                    <div class="sec-h" style="margin-top:14px">Reels / Shorts videos</div>
                    <div class="clib-multi-list" id="clib-videos-list">
                        <div class="clib-multi-row">
                            <input type="file" name="videos[]" accept="video/*" class="clib-upload-file">
                            <button type="button" class="clib-row-remove" title="Remove" hidden><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm clib-add-row" data-add="reel"><i class="ti ti-plus"></i> Add video</button>
                </div>

                <div class="clib-upload-panel" data-panel="carousel" hidden>
                    <div class="sec-h" style="margin-top:14px">Carousel images</div>
                    <p class="clib-upload-hint">Add multiple slides for one carousel set.</p>
                    <div class="clib-multi-list" id="clib-carousel-list">
                        <div class="clib-multi-row">
                            <input type="file" name="carousel_images[]" accept="image/*" class="clib-upload-file">
                            <button type="button" class="clib-row-remove" title="Remove" hidden><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm clib-add-row" data-add="carousel"><i class="ti ti-plus"></i> Add slide</button>
                </div>
            </div>

            <div class="modal-foot" style="justify-content:flex-end">
                <button type="button" class="btn btn-ghost" data-close-clib-upload-modal>Cancel</button>
                <button type="submit" class="btn btn-green"><i class="ti ti-device-floppy"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route($routeName.'.update-manual') }}" enctype="multipart/form-data" id="clib-carousel-slot-form" hidden>
    @csrf
    <input type="hidden" name="manual_type" value="carousel">
    <input type="hidden" name="manual_key" id="clib-carousel-key">
    <input type="hidden" name="carousel_slot" id="clib-carousel-slot">
    <input type="file" name="replace_image" id="clib-carousel-file" accept="image/*">
</form>

<div class="modal-overlay" id="clib-edit-modal" aria-hidden="true">
    <div class="cmo-modal clib-upload-modal" role="dialog" aria-modal="true" aria-labelledby="clib-edit-title">
        <div class="modal-head">
            <div>
                <h2 id="clib-edit-title">Edit content</h2>
                <p id="clib-edit-subtitle">Update your uploaded content.</p>
            </div>
            <button type="button" class="close-btn" data-close-clib-edit-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <form method="POST" action="{{ route($routeName.'.update-manual') }}" enctype="multipart/form-data" id="clib-edit-form">
            @csrf
            <input type="hidden" name="manual_type" id="clib-edit-type">
            <input type="hidden" name="manual_key" id="clib-edit-key">

            <div class="modal-body">
                <div class="clib-edit-panel" data-edit-panel="caption">
                    <div class="sec-h">Caption</div>
                    <textarea name="caption" id="clib-edit-caption" rows="4" placeholder="Write your captionâ€¦" class="clib-upload-textarea"></textarea>
                    <label class="clib-pair-file" style="margin-top:10px">
                        <input type="file" name="caption_image" accept="image/*" class="clib-upload-file-input" id="clib-edit-caption-image">
                        <span><i class="ti ti-photo"></i> Replace image (optional)</span>
                    </label>
                </div>

                <div class="clib-edit-panel" data-edit-panel="image" hidden>
                    <div class="sec-h">Replace image</div>
                    <input type="file" name="replace_image" accept="image/*" class="clib-upload-file">
                </div>

                <div class="clib-edit-panel" data-edit-panel="reel" hidden>
                    <div class="sec-h">Replace video</div>
                    <input type="file" name="replace_video" accept="video/*" class="clib-upload-file">
                </div>

                <div class="clib-edit-panel" data-edit-panel="carousel" hidden>
                    <div class="sec-h">Replace carousel slides</div>
                    <p class="clib-upload-hint">Upload new slides â€” they will replace the entire carousel set.</p>
                    <div class="clib-multi-list" id="clib-edit-carousel-list">
                        <div class="clib-multi-row">
                            <input type="file" name="carousel_images[]" accept="image/*" class="clib-upload-file">
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm clib-add-edit-carousel"><i class="ti ti-plus"></i> Add slide</button>
                </div>
            </div>

            <div class="modal-foot" style="justify-content:flex-end">
                <button type="button" class="btn btn-ghost" data-close-clib-edit-modal>Cancel</button>
                <button type="submit" class="btn btn-green"><i class="ti ti-device-floppy"></i> Update</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const videoModal = document.getElementById('clib-video-modal');
    const videoPlayer = document.getElementById('clib-video-modal-player');
    const videoTitle = document.getElementById('clib-video-modal-title');
    const videoSubtitle = document.getElementById('clib-video-modal-subtitle');

    const openVideoModal = (url, title = 'Reel preview') => {
        if (!videoModal || !videoPlayer || !url) return;

        videoPlayer.src = url;
        videoPlayer.currentTime = 0;
        videoPlayer.muted = false;
        if (videoTitle) videoTitle.textContent = title;
        if (videoSubtitle) videoSubtitle.textContent = 'Tap play to watch your reel with audio';

        videoModal.classList.add('is-open');
        videoModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        videoPlayer.play().catch(() => {});
    };

    const closeVideoModal = () => {
        if (!videoModal || !videoPlayer) return;

        videoPlayer.pause();
        videoPlayer.removeAttribute('src');
        videoPlayer.load();
        videoModal.classList.remove('is-open');
        videoModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.clib-reel-playable[data-video-url]').forEach((preview) => {
        preview.addEventListener('click', () => {
            openVideoModal(preview.dataset.videoUrl, preview.dataset.videoTitle || 'Reel preview');
        });

        preview.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openVideoModal(preview.dataset.videoUrl, preview.dataset.videoTitle || 'Reel preview');
            }
        });
    });

    videoModal?.querySelectorAll('[data-close-clib-video-modal]').forEach((btn) => {
        btn.addEventListener('click', closeVideoModal);
    });

    videoModal?.addEventListener('click', (event) => {
        if (event.target === videoModal) closeVideoModal();
    });

    const imageModal = document.getElementById('clib-image-modal');
    const imageModalImg = document.getElementById('clib-image-modal-img');
    const imageModalTitle = document.getElementById('clib-image-modal-title');
    const imageModalSubtitle = document.getElementById('clib-image-modal-subtitle');

    const openImageModal = (url, title = 'Image preview') => {
        if (!imageModal || !imageModalImg || !url) return;

        imageModalImg.src = url;
        imageModalImg.alt = title;
        if (imageModalTitle) imageModalTitle.textContent = title;
        if (imageModalSubtitle) imageModalSubtitle.textContent = 'Click outside or press Esc to close';

        imageModal.classList.add('is-open');
        imageModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeImageModal = () => {
        if (!imageModal || !imageModalImg) return;

        imageModalImg.removeAttribute('src');
        imageModal.classList.remove('is-open');
        imageModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.clib-image-preview[data-image-url]').forEach((preview) => {
        const openPreview = () => openImageModal(preview.dataset.imageUrl, preview.dataset.imageTitle || 'Image preview');

        preview.addEventListener('click', (event) => {
            if (event.target.closest('.clib-plan-check, .clib-plan-checkbox')) return;
            openPreview();
        });

        preview.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview();
            }
        });
    });

    imageModal?.querySelectorAll('[data-close-clib-image-modal]').forEach((btn) => {
        btn.addEventListener('click', closeImageModal);
    });

    imageModal?.addEventListener('click', (event) => {
        if (event.target === imageModal) closeImageModal();
    });

    const carouselModal = document.getElementById('clib-carousel-modal');
    const carouselModalImg = document.getElementById('clib-carousel-modal-img');
    const carouselModalTitle = document.getElementById('clib-carousel-modal-title');
    const carouselModalSubtitle = document.getElementById('clib-carousel-modal-subtitle');
    const carouselCounter = document.getElementById('clib-carousel-counter');
    const carouselPrev = document.getElementById('clib-carousel-prev');
    const carouselNext = document.getElementById('clib-carousel-next');
    let carouselImages = [];
    let carouselIndex = 0;

    const renderCarouselSlide = () => {
        if (!carouselModalImg || !carouselImages.length) return;

        carouselIndex = Math.max(0, Math.min(carouselIndex, carouselImages.length - 1));
        carouselModalImg.src = carouselImages[carouselIndex];
        carouselModalImg.alt = `Carousel slide ${carouselIndex + 1}`;

        if (carouselCounter) {
            carouselCounter.textContent = `${carouselIndex + 1} / ${carouselImages.length}`;
        }

        if (carouselPrev) carouselPrev.disabled = carouselIndex === 0;
        if (carouselNext) carouselNext.disabled = carouselIndex >= carouselImages.length - 1;
    };

    const openCarouselModal = (images, title = 'Carousel preview', startIndex = 0) => {
        if (!carouselModal || !Array.isArray(images) || !images.length) return;

        carouselImages = images.filter(Boolean);
        carouselIndex = Math.max(0, Math.min(startIndex, carouselImages.length - 1));

        if (carouselModalTitle) carouselModalTitle.textContent = title;
        if (carouselModalSubtitle) carouselModalSubtitle.textContent = 'Use arrows to browse slides';

        renderCarouselSlide();

        carouselModal.classList.add('is-open');
        carouselModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeCarouselModal = () => {
        if (!carouselModal || !carouselModalImg) return;

        carouselImages = [];
        carouselIndex = 0;
        carouselModalImg.removeAttribute('src');
        carouselModal.classList.remove('is-open');
        carouselModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.clib-carousel-preview[data-carousel-images]').forEach((grid) => {
        let images = [];

        try {
            images = JSON.parse(grid.dataset.carouselImages || '[]');
        } catch (_) {
            images = [];
        }

        const openPreview = (startIndex = 0) => {
            openCarouselModal(images, grid.dataset.carouselTitle || 'Carousel preview', startIndex);
        };

        grid.addEventListener('click', (event) => {
            if (event.target.closest('.clib-plan-check, .clib-plan-checkbox, .clib-slot-delete-form')) return;

            const cell = event.target.closest('.clib-image-cell');
            const cells = [...grid.querySelectorAll('.clib-image-cell img')].map((img) => img.closest('.clib-image-cell'));
            const startIndex = cell ? cells.indexOf(cell) : 0;

            openPreview(startIndex >= 0 ? startIndex : 0);
        });

        grid.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview(0);
            }
        });
    });

    carouselPrev?.addEventListener('click', () => {
        if (carouselIndex > 0) {
            carouselIndex -= 1;
            renderCarouselSlide();
        }
    });

    carouselNext?.addEventListener('click', () => {
        if (carouselIndex < carouselImages.length - 1) {
            carouselIndex += 1;
            renderCarouselSlide();
        }
    });

    carouselModal?.querySelectorAll('[data-close-clib-carousel-modal]').forEach((btn) => {
        btn.addEventListener('click', closeCarouselModal);
    });

    carouselModal?.addEventListener('click', (event) => {
        if (event.target === carouselModal) closeCarouselModal();
    });

    const captionModal = document.getElementById('clib-caption-modal');
    const captionModalTitle = document.getElementById('clib-caption-modal-title');
    const captionModalSubtitle = document.getElementById('clib-caption-modal-subtitle');
    const captionModalText = document.getElementById('clib-caption-modal-text');
    const captionModalCopy = document.getElementById('clib-caption-modal-copy');
    let captionModalCurrentText = '';

    const openCaptionModal = (title, text) => {
        if (!captionModal || !text) return;

        captionModalCurrentText = text;
        if (captionModalTitle) captionModalTitle.textContent = title || 'Caption preview';
        if (captionModalSubtitle) captionModalSubtitle.textContent = 'Click outside or press Esc to close';
        if (captionModalText) captionModalText.textContent = text;

        captionModal.classList.add('is-open');
        captionModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeCaptionModal = () => {
        if (!captionModal) return;

        captionModal.classList.remove('is-open');
        captionModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.clib-caption-preview').forEach((body) => {
        const openPreview = () => {
            const card = body.closest('.clib-item-card');
            const title = body.dataset.captionTitle
                || card?.querySelector('.clib-item-title')?.textContent?.trim()
                || 'Caption preview';
            const text = body.textContent?.trim() || '';

            if (!text) return;
            openCaptionModal(title, text);
        };

        body.addEventListener('click', (event) => {
            if (event.target.closest('.clib-plan-check, .clib-plan-checkbox')) return;
            openPreview();
        });

        body.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview();
            }
        });
    });

    captionModal?.querySelectorAll('[data-close-clib-caption-modal]').forEach((btn) => {
        btn.addEventListener('click', closeCaptionModal);
    });

    captionModal?.addEventListener('click', (event) => {
        if (event.target === captionModal) closeCaptionModal();
    });

    document.addEventListener('keydown', (event) => {
        if (carouselModal?.classList.contains('is-open')) {
            if (event.key === 'ArrowLeft' && carouselIndex > 0) {
                carouselIndex -= 1;
                renderCarouselSlide();
                return;
            }
            if (event.key === 'ArrowRight' && carouselIndex < carouselImages.length - 1) {
                carouselIndex += 1;
                renderCarouselSlide();
                return;
            }
        }

        if (event.key !== 'Escape') return;
        if (carouselModal?.classList.contains('is-open')) closeCarouselModal();
        else if (captionModal?.classList.contains('is-open')) closeCaptionModal();
        else if (imageModal?.classList.contains('is-open')) closeImageModal();
        else if (videoModal?.classList.contains('is-open')) closeVideoModal();
    });

    const copyText = async (text, btn) => {
        try {
            await navigator.clipboard.writeText(text);
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check"></i>';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        } catch (e) {
            alert('Could not copy to clipboard.');
        }
    };

    document.querySelectorAll('.clib-copy-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const text = btn.closest('.clib-item-card')?.querySelector('.clib-item-body')?.textContent?.trim() || '';
            copyText(text, btn);
        });
    });

    captionModalCopy?.addEventListener('click', () => {
        copyText(captionModalCurrentText, captionModalCopy);
    });

    const planSaveForm = document.getElementById('clib-plan-save-form');
    const planSaveBtn = document.getElementById('clib-save-plan-btn');
    const planInputsHost = document.getElementById('clib-plan-selected-inputs');
    const planPostTypeInput = document.getElementById('clib-plan-post-type');
    const planTypeHint = document.getElementById('pp-type-hint');

    const channelTagsHost = document.getElementById('pp-channel-tags');
    const channelEmptyLabel = document.getElementById('pp-channel-empty');
    const channelSearchInput = document.getElementById('pp-channel-search');
    const channelSelectedCount = document.getElementById('pp-selected-count');
    const channelSelectAll = document.getElementById('pp-select-all');
    const channelBody = document.getElementById('pp-channel-body');
    const channelToggle = document.getElementById('pp-channel-toggle');
    const channelList = document.getElementById('pp-channel-list');
    let activePlatformFilter = 'all';

    const getSelectedChannels = () => [...document.querySelectorAll('.pp-channel-checkbox:checked')];

    const getVisibleChannelRows = () => {
        if (!channelList) return [];
        const query = (channelSearchInput?.value || '').trim().toLowerCase();
        return [...channelList.querySelectorAll('.pp-channel-row')].filter((row) => {
            if (row.hidden) return false;
            if (!query) return true;
            const name = row.dataset.name || '';
            const subtitle = row.dataset.subtitle || '';
            return name.includes(query) || subtitle.includes(query);
        });
    };

    const renderChannelTags = () => {
        if (!channelTagsHost) return;
        const selected = getSelectedChannels();
        channelTagsHost.querySelectorAll('.pp-channel-tag').forEach((tag) => tag.remove());

        if (!selected.length) {
            if (channelEmptyLabel) channelEmptyLabel.hidden = false;
            return;
        }

        if (channelEmptyLabel) channelEmptyLabel.hidden = true;

        selected.forEach((checkbox) => {
            const tag = document.createElement('span');
            tag.className = 'pp-channel-tag';
            const avatarUrl = checkbox.dataset.avatarUrl || '';
            const avatarHtml = avatarUrl
                ? `<span class="pp-channel-avatar pp-channel-avatar--img" style="background-image:url('${avatarUrl}')"></span>`
                : `<span class="pp-channel-avatar" style="${checkbox.dataset.avatarStyle || ''}">${checkbox.dataset.initials || ''}</span>`;
            tag.innerHTML = `
                ${avatarHtml}
                ${checkbox.dataset.name || 'Account'}
                <button type="button" class="pp-channel-tag-remove" data-channel-id="${checkbox.value}" aria-label="Remove"><i class="ti ti-x"></i></button>
            `;
            channelTagsHost.appendChild(tag);
        });
    };

    const syncChannelSelectionUi = () => {
        const selected = getSelectedChannels();
        const visible = getVisibleChannelRows();
        const visibleChecked = visible.filter((row) => row.querySelector('.pp-channel-checkbox')?.checked);

        if (channelSelectedCount) {
            channelSelectedCount.textContent = `${selected.length} selected`;
        }

        if (channelSelectAll) {
            channelSelectAll.indeterminate = visibleChecked.length > 0 && visibleChecked.length < visible.length;
            channelSelectAll.checked = visible.length > 0 && visibleChecked.length === visible.length;
        }

        renderChannelTags();
        syncPlanSaveState();
    };

    const applyChannelFilters = () => {
        if (!channelList) return;
        const query = (channelSearchInput?.value || '').trim().toLowerCase();

        channelList.querySelectorAll('.pp-channel-row').forEach((row) => {
            const platform = row.dataset.platform || '';
            const matchesPlatform = activePlatformFilter === 'all' || platform === activePlatformFilter;
            const name = row.dataset.name || '';
            const subtitle = row.dataset.subtitle || '';
            const matchesSearch = !query || name.includes(query) || subtitle.includes(query);
            row.hidden = !(matchesPlatform && matchesSearch);
        });

        syncChannelSelectionUi();
    };

    document.querySelectorAll('.pp-channel-filter').forEach((chip) => {
        chip.addEventListener('click', () => {
            activePlatformFilter = chip.dataset.platformFilter || 'all';
            document.querySelectorAll('.pp-channel-filter').forEach((c) => c.classList.toggle('is-active', c === chip));
            applyChannelFilters();
        });
    });

    channelSearchInput?.addEventListener('input', applyChannelFilters);

    channelList?.addEventListener('change', (e) => {
        if (!e.target.classList.contains('pp-channel-checkbox')) return;
        e.target.closest('.pp-channel-row')?.classList.toggle('is-selected', e.target.checked);
        syncChannelSelectionUi();
    });

    channelSelectAll?.addEventListener('change', () => {
        const checked = channelSelectAll.checked;
        getVisibleChannelRows().forEach((row) => {
            const checkbox = row.querySelector('.pp-channel-checkbox');
            if (!checkbox) return;
            checkbox.checked = checked;
            row.classList.toggle('is-selected', checked);
        });
        syncChannelSelectionUi();
    });

    channelTagsHost?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.pp-channel-tag-remove');
        if (!removeBtn) return;
        const id = removeBtn.dataset.channelId;
        const checkbox = document.querySelector(`.pp-channel-checkbox[value="${id}"]`);
        if (checkbox) {
            checkbox.checked = false;
            checkbox.closest('.pp-channel-row')?.classList.remove('is-selected');
        }
        syncChannelSelectionUi();
    });

    channelToggle?.addEventListener('click', () => {
        const expanded = channelBody?.hidden !== true;
        if (channelBody) channelBody.hidden = expanded;
        channelToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        const icon = channelToggle.querySelector('i');
        if (icon) icon.className = expanded ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
    });

    const postTypeRules = {
        image: { enabled: ['caption', 'image'], required: ['caption', 'image'], hint: 'Select one caption + one image. A combined post will be created for each selected account.' },
        reel: { enabled: ['caption', 'reel'], required: ['caption', 'reel'], hint: 'Select one caption + one Reels/Shorts video. A combined post will be created for each selected account.' },
        carousel: { enabled: ['caption', 'carousel'], required: ['caption', 'carousel'], hint: 'Select one caption + one carousel set. A combined post will be created for each selected account.' },
    };

    const syncPlanSaveState = () => {
        if (!planSaveBtn) return;
        const postType = planPostTypeInput?.value || 'image';
        const rules = postTypeRules[postType];
        const checked = [...document.querySelectorAll('[data-plan-checkbox]:checked:not(:disabled)')];
        const byCategory = checked.reduce((acc, cb) => {
            const cat = cb.dataset.planCategory || '';
            acc[cat] = (acc[cat] || 0) + 1;
            return acc;
        }, {});
        const ready = rules?.required?.every((cat) => byCategory[cat] === 1) ?? false;
        const channelCount = getSelectedChannels().length;
        planSaveBtn.disabled = !ready || channelCount === 0;
        planSaveBtn.innerHTML = ready && channelCount > 0
            ? `<i class="ti ti-device-floppy"></i> Save (${channelCount} posts)`
            : '<i class="ti ti-device-floppy"></i> Save';
    };

    const applyPostType = (type) => {
        const rules = postTypeRules[type];
        if (!rules) return;

        if (planPostTypeInput) planPostTypeInput.value = type;
        if (planTypeHint) planTypeHint.textContent = rules.hint;

        document.querySelectorAll('[data-plan-checkbox]').forEach((checkbox) => {
            const category = checkbox.dataset.planCategory || '';
            const allowed = rules.enabled.includes(category);
            checkbox.disabled = !allowed;

            if (!allowed) {
                checkbox.checked = false;
                checkbox.closest('.clib-item-card')?.classList.remove('is-plan-selected');
            }

            checkbox.closest('.clib-plan-check')?.classList.toggle('is-disabled', !allowed);
        });

        document.querySelectorAll('[data-clib-column]').forEach((column) => {
            const key = column.dataset.clibColumn || '';
            column.classList.toggle('pp-column-disabled', !rules.enabled.includes(key));
        });

        syncPlanSaveState();
    };

    document.querySelectorAll('.pp-post-type-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pp-post-type-btn').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            applyPostType(btn.dataset.postType || 'image');
        });
    });

    if (document.querySelector('.pp-composer')) {
        applyPostType('image');
        applyChannelFilters();
    }

    document.querySelectorAll('[data-plan-checkbox]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            if (checkbox.disabled) {
                checkbox.checked = false;
                return;
            }

            if (checkbox.checked) {
                const category = checkbox.dataset.planCategory || '';
                document.querySelectorAll(`[data-plan-checkbox][data-plan-category="${category}"]:checked`).forEach((other) => {
                    if (other === checkbox) return;
                    other.checked = false;
                    other.closest('.clib-item-card')?.classList.remove('is-plan-selected');
                });
            }

            checkbox.closest('.clib-item-card')?.classList.toggle('is-plan-selected', checkbox.checked);
            syncPlanSaveState();
        });
    });

    if (planSaveForm && planInputsHost) {
        planSaveForm.addEventListener('submit', (e) => {
            const postType = planPostTypeInput?.value || 'image';
            const rules = postTypeRules[postType];
            const checked = [...document.querySelectorAll('[data-plan-checkbox]:checked:not(:disabled)')];
            const byCategory = {};

            checked.forEach((checkbox) => {
                const cat = checkbox.dataset.planCategory || '';
                if (!byCategory[cat]) byCategory[cat] = checkbox;
            });

            const missing = (rules?.required || []).filter((cat) => !byCategory[cat]);
            if (missing.length) {
                e.preventDefault();
                alert('Select exactly one item from each required column.');
                return;
            }

            const channels = getSelectedChannels();
            if (!channels.length) {
                e.preventDefault();
                alert('Select at least one connected social account.');
                return;
            }

            planInputsHost.innerHTML = '';
            channels.forEach((checkbox, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `social_accounts[${index}]`;
                input.value = checkbox.value;
                planInputsHost.appendChild(input);
            });

            let itemIndex = 0;
            Object.values(byCategory).forEach((checkbox) => {
                const card = checkbox.closest('.clib-item-card');
                if (!card) return;

                const bodyText = card.querySelector('.clib-item-body')?.textContent?.trim()
                    || card.dataset.planBody
                    || '';

                const fields = {
                    category: card.dataset.planCategory || '',
                    title: card.dataset.planTitle || '',
                    body: bodyText,
                    manual_type: card.dataset.planManualType || card.dataset.planCategory || '',
                    manual_key: card.dataset.planManualKey || '',
                    thumbnail_url: card.dataset.planThumbnail || '',
                    video_url: card.dataset.planVideoUrl || '',
                };

                Object.entries(fields).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${itemIndex}][${key}]`;
                    input.value = value;
                    planInputsHost.appendChild(input);
                });

                let carouselImages = [];
                try {
                    carouselImages = JSON.parse(card.dataset.planCarouselImages || '[]');
                } catch (_) {
                    carouselImages = [];
                }

                carouselImages.filter(Boolean).forEach((url, slideIndex) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${itemIndex}][carousel_images][${slideIndex}]`;
                    input.value = url;
                    planInputsHost.appendChild(input);
                });

                itemIndex++;
            });
        });

        syncPlanSaveState();
    }

    const uploadModal = document.getElementById('clib-upload-modal');
    if (uploadModal) {
        const typeInput = document.getElementById('clib-library-type');
        const panels = uploadModal.querySelectorAll('.clib-upload-panel');
        const typeCards = uploadModal.querySelectorAll('.clib-type-card');

        const syncPanelFields = (type) => {
            panels.forEach((panel) => {
                const active = panel.dataset.panel === type;
                panel.hidden = !active;
                panel.querySelectorAll('input, textarea, select').forEach((el) => {
                    el.disabled = !active;
                });
            });
        };

        const openModal = () => {
            uploadModal.classList.add('is-open');
            uploadModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            uploadModal.classList.remove('is-open');
            uploadModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        const setType = (type) => {
            typeInput.value = type;
            typeCards.forEach((card) => card.classList.toggle('active', card.dataset.libraryType === type));
            syncPanelFields(type);
        };

        setType(typeInput.value);

        document.querySelectorAll('[data-open-clib-upload-modal]').forEach((btn) => btn.addEventListener('click', openModal));
        uploadModal.querySelectorAll('[data-close-clib-upload-modal]').forEach((btn) => btn.addEventListener('click', closeModal));
        uploadModal.addEventListener('click', (e) => { if (e.target === uploadModal) closeModal(); });

        typeCards.forEach((card) => {
            card.addEventListener('click', () => setType(card.dataset.libraryType));
        });

        const rowTemplates = {
            caption: () => {
                const row = document.createElement('div');
                row.className = 'clib-pair-row';
                row.innerHTML = '<div class="clib-pair-fields"><textarea name="captions[]" rows="3" placeholder="Write your captionâ€¦" class="clib-upload-textarea"></textarea><label class="clib-pair-file"><input type="file" name="caption_images[]" accept="image/*" class="clib-upload-file-input"><span><i class="ti ti-photo"></i> Add image</span></label></div><button type="button" class="clib-row-remove" title="Remove"><i class="ti ti-x"></i></button>';
                return row;
            },
            image: () => {
                const row = document.createElement('div');
                row.className = 'clib-multi-row';
                row.innerHTML = '<input type="file" name="images[]" accept="image/*" class="clib-upload-file"><button type="button" class="clib-row-remove" title="Remove"><i class="ti ti-x"></i></button>';
                return row;
            },
            reel: () => {
                const row = document.createElement('div');
                row.className = 'clib-multi-row';
                row.innerHTML = '<input type="file" name="videos[]" accept="video/*" class="clib-upload-file"><button type="button" class="clib-row-remove" title="Remove"><i class="ti ti-x"></i></button>';
                return row;
            },
            carousel: () => {
                const row = document.createElement('div');
                row.className = 'clib-multi-row';
                row.innerHTML = '<input type="file" name="carousel_images[]" accept="image/*" class="clib-upload-file"><button type="button" class="clib-row-remove" title="Remove"><i class="ti ti-x"></i></button>';
                return row;
            },
        };

        const listIds = { caption: 'clib-captions-list', image: 'clib-images-list', reel: 'clib-videos-list', carousel: 'clib-carousel-list' };

        const refreshRemoveButtons = (list) => {
            const rows = list.querySelectorAll('.clib-multi-row, .clib-pair-row');
            rows.forEach((row) => {
                const btn = row.querySelector('.clib-row-remove');
                if (btn) btn.hidden = rows.length <= 1;
            });
        };

        uploadModal.querySelectorAll('.clib-add-row').forEach((btn) => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.add;
                const list = document.getElementById(listIds[type]);
                if (!list || !rowTemplates[type]) return;
                list.appendChild(rowTemplates[type]());
                refreshRemoveButtons(list);
            });
        });

        uploadModal.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.clib-row-remove');
            if (!removeBtn) return;
            const row = removeBtn.closest('.clib-multi-row, .clib-pair-row');
            const list = row?.parentElement;
            if (!row || !list || list.querySelectorAll('.clib-multi-row, .clib-pair-row').length <= 1) return;
            row.remove();
            refreshRemoveButtons(list);
        });

        uploadModal.addEventListener('change', (e) => {
            const input = e.target.closest('.clib-upload-file-input');
            if (!input || !input.files?.length) return;
            const label = input.closest('.clib-pair-file')?.querySelector('span');
            if (label) label.innerHTML = '<i class="ti ti-check"></i> ' + input.files[0].name;
        });

        Object.values(listIds).forEach((id) => {
            const list = document.getElementById(id);
            if (list) refreshRemoveButtons(list);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && uploadModal.classList.contains('is-open')) closeModal();
        });

        @if ($errors->any() && old('library_type'))
        setType(@json(old('library_type')));
        openModal();
        @endif
    }

    const editModal = document.getElementById('clib-edit-modal');
    if (editModal) {
        const editForm = document.getElementById('clib-edit-form');
        const editTypeInput = document.getElementById('clib-edit-type');
        const editKeyInput = document.getElementById('clib-edit-key');
        const editCaption = document.getElementById('clib-edit-caption');
        const editPanels = editModal.querySelectorAll('.clib-edit-panel');
        const editSubtitle = document.getElementById('clib-edit-subtitle');

        const openEditModal = (type, key, caption, title, focusFile = false) => {
            editTypeInput.value = type;
            editKeyInput.value = key;
            editForm.reset();
            editTypeInput.value = type;
            editKeyInput.value = key;

            editPanels.forEach((panel) => {
                const active = panel.dataset.editPanel === type;
                panel.hidden = !active;
                panel.querySelectorAll('input, textarea, select').forEach((el) => {
                    el.disabled = !active;
                });
            });

            if (type === 'caption') {
                editCaption.value = caption || '';
                editSubtitle.textContent = 'Update caption text or replace the paired image.';
            } else if (type === 'image') {
                editSubtitle.textContent = title ? `Replace image: ${title}` : 'Upload a new image file.';
            } else if (type === 'reel') {
                editSubtitle.textContent = title ? `Replace video: ${title}` : 'Upload a new video file.';
            } else {
                editSubtitle.textContent = 'Upload new slides to replace this carousel.';
            }

            editModal.classList.add('is-open');
            editModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            if (focusFile) {
                setTimeout(() => {
                    const activePanel = editModal.querySelector('.clib-edit-panel:not([hidden])');
                    activePanel?.querySelector('input[type="file"]')?.click();
                }, 200);
            }
        };

        const openEditModalFromCard = (card, focusFile = false) => {
            openEditModal(
                card.dataset.manualType,
                card.dataset.manualKey,
                card.dataset.caption ? JSON.parse(card.dataset.caption) : '',
                card.dataset.title || '',
                focusFile
            );
        };

        const closeEditModal = () => {
            editModal.classList.remove('is-open');
            editModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-edit-manual]').forEach((btn) => {
            btn.addEventListener('click', () => {
                openEditModalFromCard(btn.closest('.clib-item-card'), false);
            });
        });

        document.querySelectorAll('.clib-item-card[data-manual-key] .clib-preview-edit:not(.clib-reel-preview)').forEach((preview) => {
            preview.addEventListener('click', () => {
                openEditModalFromCard(preview.closest('.clib-item-card'), true);
            });
        });

        const carouselSlotForm = document.getElementById('clib-carousel-slot-form');
        const carouselKeyInput = document.getElementById('clib-carousel-key');
        const carouselSlotInput = document.getElementById('clib-carousel-slot');
        const carouselFileInput = document.getElementById('clib-carousel-file');

        if (carouselSlotForm && carouselFileInput) {
            document.querySelectorAll('.clib-item-card[data-manual-type="carousel"] .clib-carousel-slot').forEach((cell) => {
                cell.addEventListener('click', () => {
                    const card = cell.closest('.clib-item-card');
                    if (!card) return;

                    carouselKeyInput.value = card.dataset.manualKey || '';
                    carouselSlotInput.value = cell.dataset.slot ?? '0';

                    carouselFileInput.onchange = () => {
                        if (carouselFileInput.files?.length) {
                            carouselSlotForm.submit();
                        }
                    };

                    carouselFileInput.value = '';
                    carouselFileInput.click();
                });
            });
        }

        editModal.querySelectorAll('[data-close-clib-edit-modal]').forEach((btn) => btn.addEventListener('click', closeEditModal));
        editModal.addEventListener('click', (e) => { if (e.target === editModal) closeEditModal(); });

        const carouselList = document.getElementById('clib-edit-carousel-list');
        editModal.querySelector('.clib-add-edit-carousel')?.addEventListener('click', () => {
            if (!carouselList) return;
            const row = document.createElement('div');
            row.className = 'clib-multi-row';
            row.innerHTML = '<input type="file" name="carousel_images[]" accept="image/*" class="clib-upload-file"><button type="button" class="clib-row-remove" title="Remove"><i class="ti ti-x"></i></button>';
            carouselList.appendChild(row);
        });

        editModal.addEventListener('change', (e) => {
            const input = e.target.closest('.clib-upload-file-input');
            if (!input || !input.files?.length) return;
            const label = input.closest('.clib-pair-file')?.querySelector('span');
            if (label) label.innerHTML = '<i class="ti ti-check"></i> ' + input.files[0].name;
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && editModal.classList.contains('is-open')) closeEditModal();
        });
    }
});
</script>
@endpush
