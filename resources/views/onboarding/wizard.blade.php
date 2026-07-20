<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Brand setup — CMO AI</title>
    @include('layouts.partials.head-assets', ['withScripts' => false])
</head>
<body class="wizard-page">
@php
    $voice = $brand->voiceSettings;
    $keywords = is_array($voice?->keywords) ? implode(', ', $voice->keywords) : '';
    $tones = [
        'Professional & authoritative' => 'professional',
        'Casual & friendly' => 'casual',
        'Bold & energetic' => 'bold',
        'Educational & helpful' => 'educational',
    ];
    $currentTone = $brand->tone ?? 'Professional & authoritative';
    $initialStep = (int) ($step ?? $brand->setup_step ?? 1);
    $initialStep = max(1, min(5, $initialStep));
    $socialPlatforms = [
        ['id' => 'fb', 'platform' => 'facebook', 'oauth' => true, 'icon' => 'ti-brand-facebook', 'color' => '#1877F2', 'label' => 'Facebook'],
        ['id' => 'ig', 'platform' => 'instagram', 'oauth' => true, 'icon' => 'ti-brand-instagram', 'color' => '#E1306C', 'label' => 'Instagram'],
        ['id' => 'li', 'platform' => 'linkedin', 'oauth' => true, 'icon' => 'ti-brand-linkedin', 'color' => '#0A66C2', 'label' => 'LinkedIn'],
        ['id' => 'x', 'platform' => 'x', 'oauth' => true, 'icon' => 'ti-brand-x', 'color' => 'var(--text)', 'label' => 'X / Twitter'],
        // ['id' => 'yt', 'platform' => 'youtube', 'oauth' => false, 'icon' => 'ti-brand-youtube', 'color' => '#FF0000', 'label' => 'YouTube'],
        // ['id' => 'pi', 'platform' => 'pinterest', 'oauth' => false, 'icon' => 'ti-brand-pinterest', 'color' => '#E60023', 'label' => 'Pinterest'],
        // ['id' => 'th', 'platform' => 'threads', 'oauth' => false, 'icon' => 'ti-brand-threads', 'color' => 'var(--text)', 'label' => 'Threads'],
    ];
    $defaultSocialUrls = [
        'fb' => 'https://www.facebook.com/',
        'ig' => 'https://www.instagram.com/',
        'li' => 'https://www.linkedin.com/',
        'x' => 'https://x.com/',
    ];
    $savedSocialUrls = old('social_urls', $socialUrls ?? []);
    $savedSocialPaths = old('social_paths', []);
    $savedReferenceUrls = old('reference_urls', $referenceUrls ?? []);

    $socialPathValue = function (string $id) use ($savedSocialPaths, $savedSocialUrls, $defaultSocialUrls): string {
        if (array_key_exists($id, $savedSocialPaths)) {
            return (string) $savedSocialPaths[$id];
        }

        $prefix = $defaultSocialUrls[$id] ?? '';
        $url = (string) ($savedSocialUrls[$id] ?? '');

        if ($url === '' || $url === $prefix) {
            return '';
        }

        if ($prefix !== '' && str_starts_with($url, $prefix)) {
            return ltrim(substr($url, strlen($prefix)), '/');
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? ltrim($path, '/') : '';
    };
@endphp

<header class="wizard-header">
    <a href="{{ route('landing') }}" class="wizard-logo">
        <div class="wizard-logo-i"><i class="ti ti-speakerphone" style="color:#fff"></i></div>
        CMO <span>AI</span>
    </a>
    <div class="pill-row">
        <div class="pill" id="pill-1"><i class="ti ti-check" style="font-size:13px"></i> Business info</div>
        <div class="pill-sep"></div>
        <div class="pill" id="pill-2"><i class="ti ti-photo" style="font-size:13px"></i> Brand assets</div>
        <div class="pill-sep"></div>
        <div class="pill" id="pill-3"><i class="ti ti-link" style="font-size:13px"></i> Brand URLs</div>
        <div class="pill-sep"></div>
        <div class="pill" id="pill-4"><i class="ti ti-world" style="font-size:13px"></i> Reference URLs</div>
        <div class="pill-sep"></div>
        <div class="pill" id="pill-5"><i class="ti ti-brand-instagram" style="font-size:13px"></i> Social accounts</div>
    </div>
    <div style="width:130px"></div>
</header>

<div class="wizard-wrap">
    <aside class="wiz-sidebar">
        <h2>Brand setup</h2>
        <p>Help CMO AI understand your brand so it generates content that truly sounds like you.</p>
        <div class="step-list">
            <div class="step-item" data-go-step="1">
                <div class="step-num" id="sn-1">1</div>
                <div class="step-info">
                    <h4 id="sh-1">Business information</h4>
                    <p>Description, products, services, audience</p>
                </div>
            </div>
            <div class="step-item" data-go-step="2">
                <div class="step-num" id="sn-2">2</div>
                <div class="step-info">
                    <h4 id="sh-2">Brand assets</h4>
                    <p>Logo, images, PDFs, brand guidelines</p>
                </div>
            </div>
            <div class="step-item" data-go-step="3">
                <div class="step-num" id="sn-3">3</div>
                <div class="step-info">
                    <h4 id="sh-3">Brand Url</h4>
                    <p>Brand website and social media URLs</p>
                </div>
            </div>
            <div class="step-item" data-go-step="4">
                <div class="step-num" id="sn-4">4</div>
                <div class="step-info">
                    <h4 id="sh-4">Reference Data url</h4>
                    <p>Add up to 10 reference links for CMO AI</p>
                </div>
            </div>
            <div class="step-item" data-go-step="5">
                <div class="step-num" id="sn-5">5</div>
                <div class="step-info">
                    <h4 id="sh-5">Social presence</h4>
                    <p>Connect your social media accounts</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="wiz-main">
        @if (session('success'))
            <div class="wizard-alert success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="wizard-alert error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="wizard-alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('onboarding.wizard.step', 1) }}" enctype="multipart/form-data" id="form-step-1" class="step-content" data-step="1">
            @csrf
            <div class="step-head">
                <div class="step-eyebrow">Step 1 of 5</div>
                <h2>Business information</h2>
                <p>This helps CMO AI understand what your company does, who you serve, and how to communicate on your behalf.</p>
            </div>
            <div class="wt"><div class="wt-seg" id="wt-1a"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-1b"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-1c"></div></div>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Company name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $brand->name) }}" required>
                </div>
                <div class="field">
                    <label for="website">Website URL</label>
                    <input type="text" id="website" name="website" value="{{ old('website', $brand->website) }}" placeholder="https://example.com">
                </div>
            </div>
            <div class="field">
                <label for="company_description">Company description *</label>
                <textarea id="company_description" name="company_description" rows="3" required>{{ old('company_description', $voice?->company_description ?? $brand->short_description) }}</textarea>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="products_services">Products / services</label>
                    <textarea id="products_services" name="products_services" rows="2">{{ old('products_services', $voice?->products_services) }}</textarea>
                </div>
                <div class="field">
                    <label for="target_audience">Target audience</label>
                    <textarea id="target_audience" name="target_audience" rows="2">{{ old('target_audience', $voice?->target_audience) }}</textarea>
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="tone">Brand tone *</label>
                    <select id="tone" name="tone" required>
                        @foreach($tones as $label => $value)
                            <option value="{{ $label }}" @selected(old('tone', $currentTone) === $label)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="language">Primary language</label>
                    <select id="language" name="language">
                        @foreach(['English', 'Hindi', 'Tamil'] as $lang)
                            <option value="{{ $lang }}" @selected(old('language', $brand->language) === $lang)>{{ $lang }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="industry">Industry *</label>
                    <select id="industry" name="industry" required>
                        @foreach(['SaaS / Tech', 'E-commerce', 'Real estate', 'Healthcare', 'Agency', 'Other'] as $ind)
                            <option value="{{ $ind }}" @selected(old('industry', $brand->industry) === $ind)>{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="country">Country</label>
                    <select id="country" name="country">
                        @foreach(['India', 'United States', 'UAE'] as $c)
                            <option value="{{ $c }}" @selected(old('country', $brand->country) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="keywords">Keywords &amp; hashtags</label>
                <input type="text" id="keywords" name="keywords" value="{{ old('keywords', $keywords) }}" placeholder="growth hacking, startup marketing, AI">
            </div>
        </form>

        <form method="POST" action="{{ route('onboarding.wizard.step', 2) }}" enctype="multipart/form-data" id="form-step-2" class="step-content" data-step="2">
            @csrf
            <div class="step-head">
                <div class="step-eyebrow">Step 2 of 5</div>
                <h2>Upload brand assets</h2>
                <p>CMO AI scans everything you upload to understand your visual identity, tone of voice, products, and services.</p>
            </div>
            <div class="wt"><div class="wt-seg" id="wt-2a"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-2b"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-2c"></div></div>
            <label class="upload-zone" for="assets">
                <i class="ti ti-cloud-upload"></i>
                <h4>Drag &amp; drop files here</h4>
                <p>PDF, PNG, JPG, MP4, MP3, DOCX · Max 100 MB per file</p>
                <input type="file" id="assets" name="assets[]" multiple accept=".pdf,.png,.jpg,.jpeg,.mp4,.mp3,.docx" style="display:none">
            </label>
            @if (!empty($uploadedAssets))
                @php
                    $wizardAssets = collect($uploadedAssets);
                    $wizardGroups = [
                        'image' => ['title' => 'Photos', 'icon' => 'ti-photo', 'color' => '#EC4899', 'label' => 'Photo', 'items' => []],
                        'video' => ['title' => 'Videos', 'icon' => 'ti-video', 'color' => '#EF4444', 'label' => 'Video', 'items' => []],
                        'audio' => ['title' => 'Audio', 'icon' => 'ti-headphones', 'color' => 'var(--green)', 'label' => 'Audio', 'items' => []],
                        'pdf' => ['title' => 'PDFs', 'icon' => 'ti-file-type-pdf', 'color' => 'var(--danger)', 'label' => 'PDF', 'items' => []],
                    ];
                    foreach ($wizardAssets as $assetItem) {
                        $kind = $assetItem['kind'] ?? 'other';
                        if (isset($wizardGroups[$kind])) {
                            $wizardGroups[$kind]['items'][] = $assetItem;
                        }
                    }
                    $openKind = collect($wizardGroups)->search(fn ($g) => count($g['items']) > 0) ?: 'image';
                @endphp
                <div class="wiz-uploaded-block">
                    <div class="asset-uploaded-heading">Uploaded ({{ $wizardAssets->count() }} {{ $wizardAssets->count() === 1 ? 'file' : 'files' }})</div>
                    <div class="wiz-asset-box">
                        @foreach ($wizardGroups as $kind => $group)
                            @php $count = count($group['items']); @endphp
                            @continue($count === 0)
                            @php $isOpen = $kind === $openKind; @endphp
                            <div class="wiz-acc-section">
                                <button type="button" class="wiz-acc-header{{ $isOpen ? ' open' : '' }}" data-wiz-asset="wiz-assets-{{ $kind }}">
                                    <i class="ti {{ $group['icon'] }}" style="color:{{ $group['color'] }}"></i>
                                    <span class="wiz-acc-label">{{ $group['title'] }}</span>
                                    <span class="wiz-acc-count">{{ $count }} available</span>
                                    <i class="ti ti-chevron-down wiz-acc-chevron{{ $isOpen ? ' open' : '' }}"></i>
                                </button>
                                <div class="wiz-acc-body{{ $isOpen ? ' open' : '' }}" id="wiz-assets-{{ $kind }}">
                                    @foreach ($group['items'] as $asset)
                                        @php $url = $asset['url'] ?? null; @endphp
                                        <div class="wiz-asset-row">
                                            @if ($kind === 'image' && $url)
                                                <div class="wiz-asset-icon wiz-asset-icon--photo">
                                                    <img src="{{ $url }}" alt="{{ $asset['name'] }}" loading="lazy">
                                                </div>
                                            @else
                                                <div class="wiz-asset-icon" style="background:{{ $kind === 'video' ? '#FEF2F2' : ($kind === 'pdf' ? '#FEF2F2' : ($kind === 'audio' ? 'var(--green-lt)' : '#FDF2F8')) }}">
                                                    <i class="ti {{ $group['icon'] }}" style="color:{{ $group['color'] }}"></i>
                                                </div>
                                            @endif
                                            <div class="wiz-asset-text">
                                                <div class="wiz-asset-name" title="{{ $asset['name'] }}">{{ $asset['name'] }}</div>
                                                <div class="wiz-asset-sub">{{ $asset['size'] }} · {{ $group['label'] }}</div>
                                            </div>
                                            @if ($kind === 'image' && $url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">View</a>
                                            @elseif ($kind === 'pdf' && $url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">View</a>
                                            @elseif ($kind === 'video' && $url)
                                                <button type="button" class="btn btn-sm btn-ghost" data-asset-popup="video" data-src="{{ $url }}" data-title="{{ $asset['name'] }}">View</button>
                                            @elseif ($kind === 'audio' && $url)
                                                <button type="button" class="btn btn-sm btn-ghost" data-asset-popup="audio" data-src="{{ $url }}" data-title="{{ $asset['name'] }}">Play</button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div id="pending-asset-preview" style="display:none;margin-top:16px"></div>
        </form>

        <div class="modal-overlay" id="asset-media-modal" aria-hidden="true">
            <div class="cmo-modal asset-media-modal" role="dialog" aria-modal="true">
                <div class="modal-head">
                    <div>
                        <h2 id="asset-media-title">Preview</h2>
                    </div>
                    <button type="button" class="close-btn" id="asset-media-close" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body" id="asset-media-body"></div>
            </div>
        </div>

        <form method="POST" action="{{ route('onboarding.wizard.step', 3) }}" id="form-step-3" class="step-content" data-step="3">
            @csrf
            <div class="step-head">
                <div class="step-eyebrow">Step 3 of 5</div>
                <h2>Brand Url</h2>
                <p>Add your brand website and social media profile URLs so CMO AI can learn from your online presence.</p>
            </div>
            <div class="wt"><div class="wt-seg" id="wt-3a"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-3b"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-3c"></div></div>
            <div class="field">
                <label for="brand_website">Brand website</label>
                <input type="text" id="brand_website" name="brand_website" value="{{ old('brand_website', $brand->website) }}" placeholder="https://yourbrand.com">
            </div>
            <h3 class="wiz-section-title">Brand Social Media</h3>
            <div class="form-grid">
                @foreach($socialPlatforms as $social)
                    @php $prefix = $defaultSocialUrls[$social['id']] ?? ''; @endphp
                    <div class="field">
                        <label for="social-{{ $social['id'] }}">{{ $social['label'] }} URL</label>
                        <div class="url-affix">
                            <span class="url-affix-prefix" aria-hidden="true">{{ $prefix }}</span>
                            <input
                                type="text"
                                id="social-{{ $social['id'] }}"
                                name="social_paths[{{ $social['id'] }}]"
                                value="{{ $socialPathValue($social['id']) }}"
                                placeholder="your-page"
                                autocomplete="off"
                                spellcheck="false"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        </form>

        <form method="POST" action="{{ route('onboarding.wizard.step', 4) }}" id="form-step-4" class="step-content" data-step="4">
            @csrf
            <div class="step-head">
                <div class="step-eyebrow">Step 4 of 5</div>
                <h2>Reference Data url</h2>
                <p>Add up to 10 reference URLs — competitor sites, inspiration pages, articles, or any links that help CMO AI understand your market.</p>
            </div>
            <div class="wt"><div class="wt-seg" id="wt-4a"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-4b"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-4c"></div></div>
            <div class="ref-url-list">
                @for ($i = 0; $i < 10; $i++)
                    <div class="field">
                        <label for="reference-url-{{ $i + 1 }}">Reference URL {{ $i + 1 }}</label>
                        <input type="url" id="reference-url-{{ $i + 1 }}" name="reference_urls[]" value="{{ old('reference_urls.'.$i, $savedReferenceUrls[$i] ?? '') }}" placeholder="https://">
                    </div>
                @endfor
            </div>
        </form>

        <form method="POST" action="{{ route('onboarding.wizard.step', 5) }}" id="form-step-5" class="step-content" data-step="5">
            @csrf
            <div class="step-head">
                <div class="step-eyebrow">Step 5 of 5</div>
                <h2>Connect social accounts</h2>
                <p>Connect the platforms where you want CMO AI to auto-publish. You can add or remove accounts any time.</p>
            </div>
            <div class="wt"><div class="wt-seg" id="wt-5a"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-5b"></div><div class="wt-dot"></div><div class="wt-seg" id="wt-5c"></div></div>
            <div class="social-grid">
                @foreach($socialPlatforms as $social)
                    @php
                        $connected = isset($connectedAccounts[$social['platform']]);
                        $account = $connectedAccounts[$social['platform']] ?? null;
                    @endphp
                    <div class="sc {{ $connected ? 'connected' : '' }}" id="sc-{{ $social['id'] }}" data-social="{{ $social['id'] }}" @if($social['oauth']) data-oauth="1" @endif>
                        <div class="sc-left">
                            <i class="ti {{ $social['icon'] }}" style="color:{{ $social['color'] }}"></i>
                            <span>
                                {{ $social['label'] }}
                                @if($connected && $account)
                                    <small style="display:block;font-size:11px;color:var(--text3);font-weight:500;margin-top:2px">{{ $account->account_name }}</small>
                                @endif
                            </span>
                        </div>
                        @if($connected)
                            <span class="cb done">Connected <i class="ti ti-check" style="font-size:11px"></i></span>
                        @elseif($social['oauth'])
                            <a href="{{ route('onboarding.social.connect', ['platform' => $social['platform'], 'return' => 'onboarding.wizard']) }}" class="cb" onclick="event.stopPropagation()">Connect</a>
                        @else
                            <button type="button" class="cb" disabled title="Coming soon">Soon</button>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="privacy-note">
                <i class="ti ti-shield-check"></i>
                <span>We use official OAuth connections only. CMO AI publishes content on your behalf — it never reads your DMs, contacts, or personal data from any account.</span>
            </div>
        </form>
    </main>
</div>

<div class="wiz-footer">
    <button type="button" class="btn btn-ghost btn-sm" id="btn-back" style="visibility:hidden"><i class="ti ti-arrow-left"></i> Back</button>
    <div class="step-count" id="step-count">Step 1 of 5</div>
    <button type="button" class="btn btn-green btn-sm" id="btn-next">Continue <i class="ti ti-arrow-right"></i></button>
</div>

<script>
const TOTAL_STEPS = 5;
let cur = {{ $initialStep }};

function goStep(n) {
    cur = n;
    render();
}

function getActiveForm() {
    return document.getElementById('form-step-' + cur);
}

function next() {
    getActiveForm().requestSubmit();
}

function prev() {
    if (cur > 1) {
        cur--;
        render();
    } else {
        window.location.href = @json(route('onboarding.plan'));
    }
}

function render() {
    document.querySelectorAll('.step-content').forEach(el => {
        el.classList.toggle('active', Number(el.dataset.step) === cur);
    });
    document.getElementById('step-count').textContent = `Step ${cur} of ${TOTAL_STEPS}`;
    document.getElementById('btn-back').style.visibility = cur === 1 ? 'hidden' : 'visible';

    const nb = document.getElementById('btn-next');
    if (cur === TOTAL_STEPS) {
        nb.innerHTML = 'Finish setup <i class="ti ti-sparkles"></i>';
    } else {
        nb.innerHTML = 'Continue <i class="ti ti-arrow-right"></i>';
    }

    const pillIcons = [
        '<i class="ti ti-check" style="font-size:13px"></i> Business info',
        '<i class="ti ti-photo" style="font-size:13px"></i> Brand assets',
        '<i class="ti ti-link" style="font-size:13px"></i> Brand URLs',
        '<i class="ti ti-world" style="font-size:13px"></i> Reference URLs',
        '<i class="ti ti-brand-instagram" style="font-size:13px"></i> Social accounts'
    ];
    for (let i = 1; i <= TOTAL_STEPS; i++) {
        const el = document.getElementById('pill-' + i);
        el.className = 'pill';
        el.innerHTML = pillIcons[i - 1];
        if (i < cur) el.classList.add('done');
        else if (i === cur) el.classList.add('curr');
    }

    for (let i = 1; i <= TOTAL_STEPS; i++) {
        const el = document.getElementById('sn-' + i);
        el.className = 'step-num';
        if (i < cur) {
            el.classList.add('done');
            el.innerHTML = '<i class="ti ti-check" style="font-size:13px"></i>';
        } else if (i === cur) {
            el.classList.add('curr');
            el.textContent = i;
        } else {
            el.textContent = i;
        }
        document.getElementById('sh-' + i).className = i <= cur ? 'active' : '';
    }

    document.querySelectorAll('.step-content').forEach(form => {
        const step = Number(form.dataset.step);
        const doneCount = step < cur ? 3 : (step === cur ? Math.min(step, 3) : 0);
        form.querySelectorAll('.wt-seg').forEach((seg, i) => {
            seg.classList.toggle('done', i < doneCount);
        });
    });
}

document.querySelectorAll('[data-go-step]').forEach(el => {
    el.addEventListener('click', () => goStep(Number(el.dataset.goStep)));
});

document.getElementById('btn-next').addEventListener('click', next);
document.getElementById('btn-back').addEventListener('click', prev);

document.querySelectorAll('.sc:not([data-oauth])').forEach(card => {
    card.addEventListener('click', () => {
        const btn = card.querySelector('.cb:not(.done)');
        if (!btn || btn.disabled) return;
        const on = card.classList.toggle('connected');
        btn.className = 'cb' + (on ? ' done' : '');
        btn.innerHTML = on ? 'Connected <i class="ti ti-check" style="font-size:11px"></i>' : 'Connect';
    });
});

document.querySelectorAll('[data-wiz-asset]').forEach((header) => {
    header.addEventListener('click', () => {
        const items = document.getElementById(header.dataset.wizAsset);
        if (!items) return;
        const chev = header.querySelector('.wiz-acc-chevron');
        const open = !items.classList.contains('open');
        items.classList.toggle('open', open);
        header.classList.toggle('open', open);
        chev?.classList.toggle('open', open);
    });
});

document.getElementById('assets')?.addEventListener('change', function () {
    const zone = this.closest('.upload-zone');
    const pending = document.getElementById('pending-asset-preview');
    if (this.files.length && zone) {
        zone.querySelector('h4').textContent = this.files.length + ' file(s) selected';
    }
    if (!pending) return;

    pending.innerHTML = '';
    if (!this.files.length) {
        pending.style.display = 'none';
        return;
    }

    pending.style.display = 'block';
    pending.className = 'wiz-asset-box';
    pending.style.marginTop = '16px';

    Array.from(this.files).forEach((file) => {
        const kind = file.type.startsWith('image/') ? 'image'
            : (file.type === 'application/pdf' || /\.pdf$/i.test(file.name) ? 'pdf'
            : (file.type.startsWith('video/') ? 'video'
            : (file.type.startsWith('audio/') ? 'audio' : 'other')));
        if (kind === 'other') return;

        const url = URL.createObjectURL(file);
        const size = file.size >= 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : (Math.round(file.size / 1024) + ' KB');
        const meta = {
            image: { icon: 'ti-photo', color: '#EC4899', label: 'Photo', bg: '#FDF2F8' },
            video: { icon: 'ti-video', color: '#EF4444', label: 'Video', bg: '#FEF2F2' },
            audio: { icon: 'ti-headphones', color: 'var(--green)', label: 'Audio', bg: 'var(--green-lt)' },
            pdf: { icon: 'ti-file-type-pdf', color: 'var(--danger)', label: 'PDF', bg: '#FEF2F2' },
        }[kind];

        const action = kind === 'image' || kind === 'pdf'
            ? `<a href="${url}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">View</a>`
            : `<button type="button" class="btn btn-sm btn-ghost" data-asset-popup="${kind}" data-src="${url}" data-title="${file.name}">${kind === 'audio' ? 'Play' : 'View'}</button>`;

        const iconHtml = kind === 'image'
            ? `<div class="wiz-asset-icon wiz-asset-icon--photo"><img src="${url}" alt=""></div>`
            : `<div class="wiz-asset-icon" style="background:${meta.bg}"><i class="ti ${meta.icon}" style="color:${meta.color}"></i></div>`;

        const row = document.createElement('div');
        row.className = 'wiz-asset-row';
        row.innerHTML = `${iconHtml}<div class="wiz-asset-text"><div class="wiz-asset-name">${file.name}</div><div class="wiz-asset-sub">${size} · ${meta.label}</div></div>${action}`;
        pending.appendChild(row);
    });
});

(function () {
    const modal = document.getElementById('asset-media-modal');
    const body = document.getElementById('asset-media-body');
    const title = document.getElementById('asset-media-title');
    const closeBtn = document.getElementById('asset-media-close');

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (body) body.innerHTML = '';
    }

    function openMedia(type, src, name) {
        if (!modal || !body || !title) return;
        title.textContent = name || (type === 'audio' ? 'Audio' : 'Video');
        body.innerHTML = type === 'audio'
            ? `<audio controls autoplay style="width:100%"><source src="${src}"></audio>`
            : `<video controls autoplay playsinline style="width:100%;max-height:70vh;border-radius:12px;background:#000"><source src="${src}"></video>`;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-asset-popup]');
        if (!btn) return;
        e.preventDefault();
        openMedia(btn.dataset.assetPopup, btn.dataset.src, btn.dataset.title);
    });

    closeBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
})();

render();
</script>
</body>
</html>
