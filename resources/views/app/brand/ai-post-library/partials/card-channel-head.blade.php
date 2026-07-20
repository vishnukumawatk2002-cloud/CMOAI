@php
    $account = $publishAccount ?? [];
    $accountName = $account['name'] ?? data_get($item->metadata, 'social_account_name');
    $platformLabel = $account['platform_label'] ?? ($platform === 'x' ? 'X' : ucfirst($platform));
    $headName = $accountName ?: $platformLabel;
    $initials = $account['initials'] ?? strtoupper(substr($headName, 0, 1));
    $avatarStyle = $account['avatar_style'] ?? 'background:var(--purple)';
    $profileImage = $account['profile_image_url'] ?? null;
    $handle = $account['handle'] ?? null;
    $metaParts = $accountName
        ? collect([$accountName, $handle ? '@'.$handle : null])->filter()->all()
        : [];
@endphp

<div class="apl-head-left">
    @if ($profileImage)
        <span class="apl-page-avatar apl-page-avatar--img" style="background-image:url('{{ $profileImage }}')" title="{{ $headName }}" role="img" aria-label="{{ $headName }}"></span>
    @else
        <span class="apl-page-avatar" style="{{ $avatarStyle }}" title="{{ $headName }}">{{ $initials }}</span>
    @endif
    <div class="apl-head-info">
        <span class="apl-head-platform" title="{{ $headName }} · {{ $postTypeLabel }}">
            {{ $headName }}
            <span class="apl-head-sep">·</span>
            {{ $postTypeLabel }}
        </span>
        <div class="apl-head-sub">
            <span class="apl-channel-meta" title="{{ $platformLabel }}{{ $metaParts ? ' · '.implode(' · ', $metaParts) : '' }}">
                <i class="ti {{ $pMeta['icon'] }} apl-platform-icon-inline" style="color:{{ $pMeta['color'] }}"></i>
                <span>{{ $platformLabel }}</span>
                @foreach ($metaParts as $part)
                    <span class="apl-head-sep">·</span>
                    <span>{{ $part }}</span>
                @endforeach
            </span>
            <span class="apl-time"><i class="ti {{ $timeIcon }}"></i> {{ $displayTime }}</span>
        </div>
    </div>
</div>
