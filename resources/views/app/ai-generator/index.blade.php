@extends('layouts.app')

@section('title', 'AI Generator — '.$brand->name)
@section('pageTitle', 'AI Generator')

@section('content')
<div class="ai-generator-page">
    <div class="ai-subtab-bar">
        <button type="button" class="ai-subtab active" data-ai-tab="prompt-gen">
            <i class="ti ti-wand"></i> Prompt Generator
        </button>
        <button type="button" class="ai-subtab" data-ai-tab="content-gen">
            <i class="ti ti-sparkles"></i> Content Generator
        </button>
    </div>

    {{-- Prompt Generator --}}
    <div class="ai-pane active" id="aip-prompt-gen">
        <div class="ai-grid-2">
            <div>
                <div class="card ai-card">
                    <div class="card-hdr">
                        <h3 class="card-hdr-title"><i class="ti ti-database" style="color:var(--purple2)"></i> Brand Data Sources</h3>
                        <span class="ai-data-count" id="pg-data-count">0 sources selected</span>
                    </div>
                    <p class="ai-card-desc">Select available brand assets to generate context-aware prompts</p>

                    <div class="ai-card-body">
                    @if ($linkSources->isNotEmpty())
                        <div class="ai-source-section">
                            <button type="button" class="ai-source-header open" data-source="src-links">
                                <i class="ti ti-link" style="color:#3B82F6"></i>
                                <span class="label-text">Brand Links</span>
                                <span class="ai-source-count">{{ $linkSources->count() }} available</span>
                                <i class="ti ti-chevron-down ai-src-chevron open"></i>
                            </button>
                            <div class="ai-source-items open" id="src-links">
                                @foreach ($linkSources as $source)
                                    <label class="ai-source-item">
                                        <input type="checkbox" class="ai-src-check" checked onchange="updatePgCount()">
                                        <i class="ti {{ $source['icon'] }}" style="color:{{ $source['color'] }};font-size:15px"></i>
                                        <div class="ai-src-info">
                                            <div class="ai-src-name">{{ $source['name'] }}</div>
                                            <div class="ai-src-sub">{{ Str::limit($source['sub'], 42) }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($photoAssets->isNotEmpty())
                        <div class="ai-source-section">
                            <button type="button" class="ai-source-header" data-source="src-photos">
                                <i class="ti ti-photo" style="color:#EC4899"></i>
                                <span class="label-text">Photos</span>
                                <span class="ai-source-count">{{ $photoAssets->count() }} available</span>
                                <i class="ti ti-chevron-down ai-src-chevron"></i>
                            </button>
                            <div class="ai-source-items" id="src-photos">
                                @foreach ($photoAssets as $asset)
                                    <label class="ai-source-item">
                                        <input type="checkbox" class="ai-src-check" onchange="updatePgCount()">
                                        <div class="ai-src-thumb">🖼</div>
                                        <div class="ai-src-info">
                                            <div class="ai-src-name">{{ $asset->file_name }}</div>
                                            <div class="ai-src-sub">{{ number_format(($asset->file_size ?? 0) / 1048576, 1) }} MB · Photo</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($videoAssets->isNotEmpty())
                        <div class="ai-source-section">
                            <button type="button" class="ai-source-header" data-source="src-videos">
                                <i class="ti ti-video" style="color:#EF4444"></i>
                                <span class="label-text">Videos</span>
                                <span class="ai-source-count">{{ $videoAssets->count() }} available</span>
                                <i class="ti ti-chevron-down ai-src-chevron"></i>
                            </button>
                            <div class="ai-source-items" id="src-videos">
                                @foreach ($videoAssets as $asset)
                                    <label class="ai-source-item">
                                        <input type="checkbox" class="ai-src-check" onchange="updatePgCount()">
                                        <div class="ai-src-thumb" style="background:#FEF2F2">🎬</div>
                                        <div class="ai-src-info">
                                            <div class="ai-src-name">{{ $asset->file_name }}</div>
                                            <div class="ai-src-sub">{{ number_format(($asset->file_size ?? 0) / 1048576, 1) }} MB · Video</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($linkSources->isEmpty() && $photoAssets->isEmpty() && $videoAssets->isEmpty())
                        <p class="ai-empty-note">Complete brand onboarding to add links, photos, and assets for smarter prompts.</p>
                    @endif
                    </div>
                </div>
            </div>

            <div>
                <div class="card ai-card">
                    <div class="card-hdr">
                        <h3 class="card-hdr-title"><i class="ti ti-wand" style="color:var(--purple2)"></i> Generate Prompts</h3>
                    </div>

                    <div class="ai-card-body">
                    <div class="ai-field">
                        <label for="pg-goal">Goal / Campaign theme</label>
                        <input type="text" id="pg-goal" class="ai-input" placeholder="e.g. Summer sale, product launch, brand awareness…">
                    </div>
                    <div class="ai-field">
                        <label for="pg-audience">Target audience</label>
                        <input type="text" id="pg-audience" class="ai-input" value="{{ $defaultAudience }}" placeholder="e.g. Young professionals, parents, food lovers…">
                    </div>
                    <div class="ai-field">
                        <label for="pg-tone">Tone of voice</label>
                        <select id="pg-tone" class="ai-input">
                            @foreach (array_unique(array_filter(['Friendly & Warm', 'Professional & Formal', 'Playful & Fun', 'Inspiring & Motivational', 'Urgent & Promotional', 'Educational & Informative', $defaultTone])) as $tone)
                                <option @selected($tone === $defaultTone)>{{ $tone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ai-field">
                        <label>Number of prompts to generate</label>
                        <div class="pg-count-row">
                            <button type="button" class="pg-count-btn active" data-count="3">3</button>
                            <button type="button" class="pg-count-btn" data-count="5">5</button>
                            <button type="button" class="pg-count-btn" data-count="10">10</button>
                        </div>
                    </div>

                    <button type="button" class="btn btn-purple ai-generate-btn" id="pg-generate-btn" onclick="generatePrompts()">
                        <i class="ti ti-wand"></i> Generate prompts from brand data
                    </button>
                    </div>
                </div>

                @if ($brand->suggestedPrompts->isNotEmpty())
                    <div class="card ai-card" id="pg-saved" style="margin-top:16px">
                        <div class="card-hdr">
                            <h3 class="card-hdr-title">Saved AI Prompts</h3>
                        </div>
                        <div class="ai-card-body">
                        @foreach ($brand->suggestedPrompts as $prompt)
                            <div class="prompt-card">
                                <div class="prompt-card-num">{{ $prompt->label }}</div>
                                <div class="prompt-card-text">{{ $prompt->prompt_text }}</div>
                                @if ($prompt->platform)
                                    <div class="prompt-card-tags">
                                        <span class="prompt-tag">#{{ $prompt->platform }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        </div>
                    </div>
                @endif

                <div id="pg-output" style="display:none">
                    <div class="card ai-card">
                        <div class="card-hdr">
                            <h3 class="card-hdr-title">Generated Prompts</h3>
                            <div class="card-hdr-actions">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="generatePrompts()"><i class="ti ti-refresh"></i> Regenerate</button>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="copyAllPrompts()"><i class="ti ti-copy"></i> Copy all</button>
                            </div>
                        </div>
                        <div class="ai-card-body">
                            <div id="pg-prompt-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Generator --}}
    <div class="ai-pane" id="aip-content-gen">
        <div class="cg-type-bar">
            @foreach ([
                'post' => ['ti-file-text', 'Post'],
                'carousel' => ['ti-layout-columns', 'Carousel'],
                'reel_script' => ['ti-player-play', 'Reel'],
                'image_caption' => ['ti-photo', 'Image caption'],
                'hashtags' => ['ti-hash', 'Hashtags'],
            ] as $val => [$icon, $label])
                <button type="button" class="cg-type-opt {{ $loop->first ? 'active' : '' }}" data-type="{{ $val }}">
                    <i class="ti {{ $icon }}"></i><span>{{ $label }}</span>
                </button>
            @endforeach
        </div>

        <form method="POST" action="{{ route('app.content.generate') }}" class="ai-grid-2" id="content-gen-form">
            @csrf
            <input type="hidden" name="content_type" id="cg-content-type" value="post">
            <div class="card ai-card">
                <div class="card-hdr">
                    <h3 class="card-hdr-title" id="cg-type-label"><i class="ti ti-file-text" style="color:var(--purple2)"></i> Post</h3>
                </div>

                <div class="ai-field">
                    <label>Platform</label>
                    <div class="cg-plat-row">
                        @foreach (['linkedin', 'instagram', 'x', 'facebook', 'youtube'] as $p)
                            <label class="cg-plat-pill active">
                                <input type="checkbox" name="platforms[]" value="{{ $p }}" checked hidden>
                                <i class="ti ti-brand-{{ $p === 'x' ? 'x' : $p }}"></i> {{ ucfirst($p) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="ai-field">
                    <label for="cg-prompt">Topic / what to create</label>
                    <textarea name="prompt" id="cg-prompt" class="ai-input ai-textarea" rows="4" required placeholder="e.g. Promote our summer sale with 40% off this weekend…">{{ old('prompt') }}</textarea>
                    @error('prompt')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="btn btn-purple ai-generate-btn">
                    <i class="ti ti-sparkles"></i> Generate content
                </button>
            </div>

            <div class="card ai-card ai-empty-panel">
                <div class="ai-empty-state">
                    <div class="ai-empty-icon"><i class="ti ti-sparkles"></i></div>
                    <h3>Ready to generate</h3>
                    <p>Fill in the details and click <strong>Generate content</strong>. Results appear in your Content Library.</p>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const brandName = @json($brand->name);
let pgCount = 3;

document.querySelectorAll('[data-ai-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.ai-subtab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ai-pane').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('aip-' + tab.dataset.aiTab)?.classList.add('active');
    });
});

document.querySelectorAll('.ai-source-header').forEach((header) => {
    header.addEventListener('click', () => {
        const id = header.dataset.source;
        const items = document.getElementById(id);
        const chev = header.querySelector('.ai-src-chevron');
        const open = !items.classList.contains('open');
        items.classList.toggle('open', open);
        header.classList.toggle('open', open);
        chev?.classList.toggle('open', open);
    });
});

document.querySelectorAll('.pg-count-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.pg-count-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        pgCount = parseInt(btn.dataset.count, 10);
    });
});

document.querySelectorAll('.cg-type-opt').forEach((opt) => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.cg-type-opt').forEach(o => o.classList.remove('active'));
        opt.classList.add('active');
        document.getElementById('cg-content-type').value = opt.dataset.type;
        const labels = {
            post: ['ti-file-text', 'Post'],
            carousel: ['ti-layout-columns', 'Carousel'],
            reel_script: ['ti-player-play', 'Reel script'],
            image_caption: ['ti-photo', 'Image caption'],
            hashtags: ['ti-hash', 'Hashtags'],
        };
        const [icon, label] = labels[opt.dataset.type] || ['ti-sparkles', 'Content'];
        document.getElementById('cg-type-label').innerHTML = `<i class="ti ${icon}" style="color:var(--purple2)"></i> ${label}`;
    });
});

document.querySelectorAll('.cg-plat-pill').forEach((pill) => {
    pill.addEventListener('click', () => {
        const input = pill.querySelector('input');
        if (!input) return;
        input.checked = !input.checked;
        pill.classList.toggle('active', input.checked);
    });
});

function updatePgCount() {
    const checked = document.querySelectorAll('.ai-src-check:checked').length;
    const el = document.getElementById('pg-data-count');
    if (el) el.textContent = checked + ' source' + (checked !== 1 ? 's' : '') + ' selected';
}

function generatePrompts() {
    const goal = document.getElementById('pg-goal')?.value || 'brand awareness';
    const audience = document.getElementById('pg-audience')?.value || 'general audience';
    const tone = document.getElementById('pg-tone')?.value || 'Friendly & Warm';
    const out = document.getElementById('pg-output');
    const list = document.getElementById('pg-prompt-list');
    if (!out || !list) return;

    list.innerHTML = '<div class="ai-loading-inline">Generating prompts…</div>';
    out.style.display = 'block';

    setTimeout(() => {
        const templates = [
            { title: 'Caption: ' + goal, body: `Write a ${tone} Instagram caption for ${brandName} about: ${goal}. Target: ${audience}. Include urgency and a strong CTA.`, tags: ['caption', 'instagram'] },
            { title: 'Reel Script', body: `Create a 30-second reel script for ${brandName}. Hook in 3 seconds, showcase ${goal}, end with CTA. Audience: ${audience}.`, tags: ['reel', 'video'] },
            { title: 'Facebook Post', body: `Write a conversational Facebook post for ${brandName} about ${goal}. Connect with ${audience}. Ask a question to boost engagement.`, tags: ['facebook', 'engagement'] },
            { title: 'LinkedIn Post', body: `Draft a LinkedIn post for ${brandName} presenting ${goal} professionally. Audience: ${audience}. End with a subtle CTA.`, tags: ['linkedin', 'professional'] },
            { title: 'Carousel Content', body: `Design a 5-slide Instagram carousel for ${brandName} educating ${audience} about ${goal}. Each slide: heading + 2-3 lines + visual direction.`, tags: ['carousel', 'educational'] },
            { title: 'Story Series', body: `Plan a 3-part Instagram story sequence for ${brandName} promoting ${goal} to ${audience}. Include poll/question stickers.`, tags: ['stories', 'instagram'] },
            { title: 'Email Snippet', body: `Write a short email promo for ${brandName} about ${goal}. Tone: ${tone}. Audience: ${audience}.`, tags: ['email', 'promo'] },
            { title: 'Ad Copy', body: `Create paid ad copy for ${brandName} — headline, primary text, and CTA for ${goal}.`, tags: ['ads', 'conversion'] },
            { title: 'Blog Intro', body: `Write an SEO-friendly blog intro for ${brandName} on the topic: ${goal}. Audience: ${audience}.`, tags: ['blog', 'seo'] },
            { title: 'Hashtag Set', body: `Suggest 20 relevant hashtags for ${brandName} content about ${goal} targeting ${audience}.`, tags: ['hashtags', 'discovery'] },
        ];

        list.innerHTML = templates.slice(0, pgCount).map((p, i) => `
            <div class="prompt-card">
                <div class="prompt-card-head">
                    <div class="prompt-card-num">${i + 1}. ${p.title}</div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="copyText(${JSON.stringify(p.body)})"><i class="ti ti-copy"></i> Copy</button>
                </div>
                <div class="prompt-card-text">${p.body}</div>
                <div class="prompt-card-tags">${p.tags.map(t => `<span class="prompt-tag">#${t}</span>`).join('')}</div>
            </div>
        `).join('');
    }, 800);
}

function copyText(text) {
    navigator.clipboard.writeText(text).catch(() => {});
}

function copyAllPrompts() {
    const list = document.getElementById('pg-prompt-list');
    if (list) navigator.clipboard.writeText(list.innerText).catch(() => {});
}

updatePgCount();
</script>
@endpush
