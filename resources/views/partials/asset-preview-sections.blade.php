@php
    $items = collect($assets ?? []);
    $showCountHeading = $showCountHeading ?? true;
    $groupOrder = ['image', 'pdf', 'video', 'audio', 'other'];
    $groupMeta = [
        'image' => ['title' => 'Images', 'icon' => 'ti-photo', 'color' => 'var(--purple2)'],
        'pdf' => ['title' => 'PDFs', 'icon' => 'ti-file-type-pdf', 'color' => 'var(--danger)'],
        'video' => ['title' => 'Videos', 'icon' => 'ti-video', 'color' => 'var(--purple)'],
        'audio' => ['title' => 'Audio', 'icon' => 'ti-headphones', 'color' => 'var(--green)'],
        'other' => ['title' => 'Other files', 'icon' => 'ti-file', 'color' => 'var(--text3)'],
    ];
    $grouped = $items->groupBy(fn ($asset) => $asset['kind'] ?? 'other');
@endphp

@if ($items->isNotEmpty())
    @if ($showCountHeading)
        <div class="asset-uploaded-heading">Uploaded ({{ $items->count() }} {{ $items->count() === 1 ? 'file' : 'files' }})</div>
    @endif

    @foreach ($groupOrder as $kind)
        @php $groupItems = $grouped->get($kind, collect()); @endphp
        @continue($groupItems->isEmpty())

        <div class="asset-group">
            <div class="asset-group-heading">
                <i class="ti {{ $groupMeta[$kind]['icon'] }}" style="color:{{ $groupMeta[$kind]['color'] }}"></i>
                <span>{{ $groupMeta[$kind]['title'] }}</span>
                <span class="asset-group-count">{{ $groupItems->count() }}</span>
            </div>
            <div class="asset-preview-grid">
                @foreach ($groupItems as $asset)
                    @php
                        $url = $asset['url'] ?? null;
                        $icon = $groupMeta[$kind]['icon'];
                        $iconColor = $groupMeta[$kind]['color'];
                    @endphp
                    <div class="asset-preview-card" data-kind="{{ $kind }}">
                        <div class="asset-preview-media">
                            <i class="ti {{ $icon }} asset-preview-icon" style="color:{{ $iconColor }}"></i>
                            @if ($kind === 'image' && $url)
                                <img src="{{ $url }}" alt="{{ $asset['name'] }}" loading="lazy">
                            @endif
                        </div>
                        <div class="asset-preview-meta">
                            <div class="asset-preview-name" title="{{ $asset['name'] }}">{{ $asset['name'] }}</div>
                            <div class="asset-preview-size">{{ $asset['size'] }}</div>
                        </div>
                        <div class="asset-preview-actions">
                            @if ($kind === 'pdf' && $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">View PDF</a>
                            @elseif ($kind === 'video' && $url)
                                <button type="button" class="btn btn-sm btn-ghost" data-asset-popup="video" data-src="{{ $url }}" data-title="{{ $asset['name'] }}">View Video</button>
                            @elseif ($kind === 'audio' && $url)
                                <button type="button" class="btn btn-sm btn-ghost" data-asset-popup="audio" data-src="{{ $url }}" data-title="{{ $asset['name'] }}">Play Audio</button>
                            @elseif ($kind === 'image' && $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">View</a>
                            @else
                                <span class="asset-preview-ok"><i class="ti ti-check"></i></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif
