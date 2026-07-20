@extends('layouts.app')

@section('title', 'Brand Data Source — '.$brand->name)
@section('pageTitle', 'Brand Data Source')

@section('topbarExtra')
    <a href="{{ route('app.brand.knowledge-base') }}" class="btn btn-ghost btn-sm"><i class="ti ti-brain"></i> Knowledge base</a>
    <a href="{{ route('onboarding.wizard') }}" class="btn btn-ghost btn-sm"><i class="ti ti-edit"></i> Update sources</a>
@endsection

@section('content')
@php
    $kb = $profile['knowledge_base'] ?? null;
    $kbReady = $kb?->training_status === 'complete';
@endphp

<div class="bds-page">
    <div class="bds-hero">
        <div class="bds-hero-main">
            <div class="bds-hero-icon"><i class="ti ti-database"></i></div>
            <div>
                <h1 class="bds-hero-title">Brand Data Sources</h1>
                <p class="bds-hero-sub">Everything CMO AI uses to learn your brand voice and generate on-brand content</p>
            </div>
        </div>
        <div class="bds-hero-status">
            @if ($kbReady)
                <span class="bds-status-pill ready"><i class="ti ti-check"></i> AI trained</span>
            @else
                <span class="bds-status-pill learning"><i class="ti ti-loader"></i> Learning brand</span>
            @endif
            <span class="bds-status-pill count" title="Brand created">
                <i class="ti ti-calendar-plus"></i> Created {{ $brand->created_at?->format('M j, Y g:i A') }}
            </span>
            @if ($brand->sources_updated_at)
                <span class="bds-status-pill count" title="Sources last updated">
                    <i class="ti ti-clock"></i> Updated {{ $brand->sources_updated_at->format('M j, Y g:i A') }}
                </span>
            @endif
            <span class="bds-status-pill count">{{ $stats['total'] }} total sources</span>
        </div>
    </div>

    <div class="bds-stats">
        <div class="bds-stat">
            <div class="bds-stat-icon" style="background:#FDF2F8"><i class="ti ti-photo" style="color:#EC4899"></i></div>
            <div class="bds-stat-val">{{ $stats['assets'] }}</div>
            <div class="bds-stat-label">Brand assets</div>
        </div>
        <div class="bds-stat">
            <div class="bds-stat-icon" style="background:#EFF6FF"><i class="ti ti-link" style="color:#3B82F6"></i></div>
            <div class="bds-stat-val">{{ $stats['links'] }}</div>
            <div class="bds-stat-label">Brand links</div>
        </div>
        <div class="bds-stat">
            <div class="bds-stat-icon" style="background:var(--warning-lt)"><i class="ti ti-bookmark" style="color:#854D0E"></i></div>
            <div class="bds-stat-val">{{ $stats['references'] }}</div>
            <div class="bds-stat-label">Reference URLs</div>
        </div>
        <div class="bds-stat">
            <div class="bds-stat-icon" style="background:var(--green-lt)"><i class="ti ti-plug" style="color:var(--green)"></i></div>
            <div class="bds-stat-val">{{ $stats['social'] }}</div>
            <div class="bds-stat-label">Social accounts</div>
        </div>
    </div>

    {{-- Step 1: Business information --}}
    <div class="bds-card bds-card-full">
        <div class="bds-card-head">
            <span class="bds-step-num">1</span>
            <div>
                <div class="bds-card-title">{{ $profile['step1']['title'] }}</div>
                <div class="bds-card-sub">Description, products, services, audience</div>
            </div>
            <span class="bds-count-badge">{{ count($profile['step1']['fields']) }} fields</span>
        </div>
        @if (count($profile['step1']['fields']))
            <div class="bds-info-grid">
                @foreach ($profile['step1']['fields'] as $field)
                    <div class="bds-info-field">
                        <div class="bds-info-label">{{ $field['label'] }}</div>
                        <div class="bds-info-value">
                            @if (($field['label'] ?? '') === 'Website URL' && filled($field['value']))
                                <a href="{{ $field['value'] }}" target="_blank" rel="noopener">{{ $field['value'] }}</a>
                            @else
                                {{ $field['value'] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bds-empty">
                <i class="ti ti-building-store"></i>
                <p>No business information saved yet.</p>
                <a href="{{ route('onboarding.wizard') }}" class="btn btn-ghost btn-sm">Add business info</a>
            </div>
        @endif
    </div>

    <div class="bds-grid">
        {{-- Step 2: Brand assets --}}
        <div class="bds-card">
            <div class="bds-card-head">
                <span class="bds-step-num">2</span>
                <div>
                    <div class="bds-card-title">{{ $profile['step2']['title'] }}</div>
                    <div class="bds-card-sub">Logo, images, PDFs, brand guidelines</div>
                </div>
                <span class="bds-count-badge">{{ count($profile['step2']['assets']) }}</span>
            </div>
            @if (count($profile['step2']['assets']))
                <div class="bds-file-list">
                    @foreach ($profile['step2']['assets'] as $asset)
                        @php
                            $type = (string) ($asset['type'] ?? $asset['kind'] ?? '');
                            $icon = str_starts_with($type, 'image') || $type === 'logo' || $type === 'image'
                                ? 'ti-photo'
                                : (str_starts_with($type, 'video') || $type === 'video'
                                    ? 'ti-video'
                                    : (str_starts_with($type, 'audio') || $type === 'audio'
                                        ? 'ti-music'
                                        : ($type === 'pdf' ? 'ti-file-type-pdf' : 'ti-file')));
                            $iconColor = str_starts_with($type, 'image') || $type === 'logo' || $type === 'image'
                                ? '#EC4899'
                                : (str_starts_with($type, 'video') || $type === 'video'
                                    ? '#EF4444'
                                    : ($type === 'pdf' ? 'var(--danger)' : 'var(--purple2)'));
                        @endphp
                        <div class="bds-file-row">
                            <i class="ti {{ $icon }} bds-file-icon" style="color:{{ $iconColor }}"></i>
                            <span class="bds-file-name">{{ $asset['name'] }}</span>
                            <span class="bds-file-size">{{ $asset['size'] }}</span>
                            <i class="ti ti-check bds-file-check"></i>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bds-empty">
                    <i class="ti ti-upload"></i>
                    <p>No brand assets uploaded yet.</p>
                    <a href="{{ route('onboarding.wizard') }}" class="btn btn-ghost btn-sm">Upload assets</a>
                </div>
            @endif
        </div>

        {{-- Step 3: Brand URLs --}}
        <div class="bds-card">
            <div class="bds-card-head">
                <span class="bds-step-num">3</span>
                <div>
                    <div class="bds-card-title">{{ $profile['step3']['title'] }}</div>
                    <div class="bds-card-sub">Brand website and social media URLs</div>
                </div>
                <span class="bds-count-badge">{{ $stats['links'] }}</span>
            </div>
            @if ($profile['step3']['website'] || count($profile['step3']['social_urls']))
                @if ($profile['step3']['website'])
                    <div class="bds-url-block">
                        <div class="bds-url-label">Brand website</div>
                        <a href="{{ $profile['step3']['website'] }}" target="_blank" rel="noopener" class="bds-url-link">
                            <i class="ti ti-world"></i>
                            <span>{{ $profile['step3']['website'] }}</span>
                            <i class="ti ti-external-link"></i>
                        </a>
                    </div>
                @endif
                @if (count($profile['step3']['social_urls']))
                    <div class="bds-url-label" style="margin-top:{{ $profile['step3']['website'] ? '14px' : '0' }}">Brand social media</div>
                    <div class="bds-social-list">
                        @foreach ($profile['step3']['social_urls'] as $social)
                            @php
                                $platform = $social['platform'] ?? '';
                                $platIcon = match ($platform) {
                                    'fb', 'facebook' => 'ti-brand-facebook',
                                    'ig', 'instagram' => 'ti-brand-instagram',
                                    'li', 'linkedin' => 'ti-brand-linkedin',
                                    'x', 'twitter' => 'ti-brand-x',
                                    'yt', 'youtube' => 'ti-brand-youtube',
                                    default => 'ti-link',
                                };
                            @endphp
                            <div class="bds-social-row">
                                <div class="bds-social-label"><i class="ti {{ $platIcon }}"></i> {{ $social['label'] }}</div>
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="bds-url-link compact">
                                    <span>{{ \Illuminate\Support\Str::limit($social['url'], 48) }}</span>
                                    <i class="ti ti-external-link"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="bds-empty">
                    <i class="ti ti-link"></i>
                    <p>No brand URLs saved yet.</p>
                    <a href="{{ route('onboarding.wizard') }}" class="btn btn-ghost btn-sm">Add URLs</a>
                </div>
            @endif
        </div>

        {{-- Step 4: Reference URLs --}}
        <div class="bds-card">
            <div class="bds-card-head">
                <span class="bds-step-num">4</span>
                <div>
                    <div class="bds-card-title">{{ $profile['step4']['title'] }}</div>
                    <div class="bds-card-sub">Reference links for CMO AI</div>
                </div>
                <span class="bds-count-badge">{{ count($profile['step4']['urls']) }}</span>
            </div>
            @if (count($profile['step4']['urls']))
                <div class="bds-ref-list">
                    @foreach ($profile['step4']['urls'] as $index => $url)
                        <div class="bds-ref-row">
                            <div class="bds-url-label">Reference URL {{ $index + 1 }}</div>
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="bds-url-link">
                                <i class="ti ti-bookmark"></i>
                                <span>{{ $url }}</span>
                                <i class="ti ti-external-link"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bds-empty">
                    <i class="ti ti-bookmark"></i>
                    <p>No reference URLs added yet.</p>
                    <a href="{{ route('onboarding.wizard') }}" class="btn btn-ghost btn-sm">Add references</a>
                </div>
            @endif
        </div>

        {{-- Step 5: Social presence --}}
        <div class="bds-card">
            <div class="bds-card-head">
                <span class="bds-step-num">5</span>
                <div>
                    <div class="bds-card-title">{{ $profile['step5']['title'] }}</div>
                    <div class="bds-card-sub">Connected social media accounts</div>
                </div>
                <span class="bds-count-badge">{{ $profile['step5']['accounts']->count() }}</span>
            </div>
            @if ($profile['step5']['accounts']->isNotEmpty())
                <div class="bds-account-list">
                    @foreach ($profile['step5']['accounts'] as $account)
                        <div class="bds-account-row">
                            <div class="bds-account-icon">
                                <i class="ti ti-brand-{{ $account->platform === 'x' ? 'x' : $account->platform }}"></i>
                            </div>
                            <div class="bds-account-info">
                                <div class="bds-account-name">{{ $account->account_name ?? ucfirst($account->platform) }}</div>
                                <div class="bds-account-platform">{{ ucfirst($account->platform) }}</div>
                            </div>
                            <span class="badge {{ $account->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($account->status) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bds-empty">
                    <i class="ti ti-plug"></i>
                    <p>No social accounts connected yet.</p>
                    <a href="{{ route('app.brand.social-accounts') }}" class="btn btn-ghost btn-sm">Connect accounts</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
