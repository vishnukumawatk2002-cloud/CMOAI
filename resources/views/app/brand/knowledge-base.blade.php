@extends('layouts.app')

@section('title', 'Brand Knowledge Base — '.$brand->name)
@section('pageTitle', 'Brand Knowledge Base')

@section('topbarExtra')
    <a href="{{ route('app.brand.content-suggestions') }}" class="btn btn-ghost btn-sm"><i class="ti ti-bulb"></i> Content suggestions</a>
    <a href="{{ route('app.brand.data-sources') }}" class="btn btn-ghost btn-sm"><i class="ti ti-database"></i> Data sources</a>
@endsection

@section('content')
@php
    $kb = $profile['knowledge_base'] ?? null;
    $kbReady = $kb?->training_status === 'complete';
    $promptCount = ($profile['suggested_prompts'] ?? collect())->count();
    $ai = $profile['ai_analysis'] ?? [];
    $aiProvider = $ai['provider'] ?? 'local';
    $aiModel = $ai['model'] ?? null;
    $aiLive = $kbReady && in_array($aiProvider, ['openrouter', 'bluesminds', 'groq', 'openai', 'gemini'], true);
    $aiProviderLabel = match ($aiProvider) {
        'openrouter' => 'OpenRouter',
        'bluesminds' => 'Bluesminds',
        'groq' => 'Groq',
        'openai' => 'OpenAI',
        'gemini' => 'Gemini',
        default => ucfirst($aiProvider),
    };
@endphp

<div class="bds-page">
    <div class="bds-hero">
        <div class="bds-hero-main">
            <div class="bds-hero-icon" style="background:var(--green)"><i class="ti ti-brain"></i></div>
            <div>
                <h1 class="bds-hero-title">Brand Knowledge Base</h1>
                <p class="bds-hero-sub">AI-generated insights and prompts from your brand data sources</p>
            </div>
        </div>
        <div class="bds-hero-status">
            @if ($aiLive)
                <span class="bds-status-pill groq-live" title="{{ $aiProviderLabel }} AI response received">
                    <i class="ti ti-bolt"></i> {{ $aiProviderLabel }} live
                </span>
                @if ($aiModel)
                    <span class="bds-status-pill groq-model" title="AI model">{{ $aiModel }}</span>
                @endif
            @elseif ($kbReady && ! $aiLive)
                <span class="bds-status-pill groq-fallback" title="External AI was not used for this brand">
                    <i class="ti ti-alert-triangle"></i> {{ $aiProviderLabel }} fallback
                </span>
            @elseif ($kb)
                <span class="bds-status-pill learning"><i class="ti ti-loader"></i> Learning brand</span>
            @else
                <span class="bds-status-pill count"><i class="ti ti-clock"></i> Not trained yet</span>
            @endif
            @if ($kbReady)
                <span class="bds-status-pill ready"><i class="ti ti-check"></i> AI trained</span>
            @endif
            <span class="bds-status-pill count" title="Brand created">
                <i class="ti ti-calendar-plus"></i> Created {{ $brand->created_at?->format('M j, Y g:i A') }}
            </span>
            @if ($brand->sources_updated_at)
                <span class="bds-status-pill count" title="Sources last updated">
                    <i class="ti ti-clock"></i> Updated {{ $brand->sources_updated_at->format('M j, Y g:i A') }}
                </span>
            @endif
            <span class="bds-status-pill count">{{ $promptCount }} prompts</span>
            {{-- Hero Regenerate — hidden per request
            @if ($kb)
                <button type="button" class="btn btn-ghost btn-sm bds-regenerate-btn" id="bds-regenerate-hero">
                    <i class="ti ti-refresh"></i> Regenerate
                </button>
            @endif
            --}}
        </div>
    </div>

    @if ($featureLocked ?? ! ($canAccessKnowledgeBase ?? true))
        @include('app.brand.partials.plan-upgrade')
    @else
    <div class="bds-regenerate-loader" id="bds-regenerate-loader" aria-hidden="true">
        <div class="bds-regenerate-loader-card">
            <span class="bds-regenerate-loader-ring"></span>
            <div class="bds-regenerate-loader-title">Regenerating AI response…</div>
            <p class="bds-regenerate-loader-sub">Groq / AI se naya brand summary aur prompts generate ho rahe hain</p>
        </div>
    </div>

    <div id="bds-kb-dynamic-root">
    @include('app.brand.partials.knowledge-base-panel')
    </div>
    @endif
</div>
@endsection

@if (! ($featureLocked ?? ! ($canAccessKnowledgeBase ?? true)))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const regenerateUrl = @json(route('app.brand.knowledge-base.regenerate'));
    const promptRegenerateUrl = @json(route('app.brand.content-suggestions.regenerate-prompt'));
    const loader = document.getElementById('bds-regenerate-loader');

    const copyBtn = document.getElementById('bds-copy-ai-response');
    const pre = document.getElementById('bds-ai-response-json');
    copyBtn?.addEventListener('click', async function () {
        if (!pre) return;
        try {
            await navigator.clipboard.writeText(pre.textContent.trim());
            const original = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="ti ti-check"></i> Copied';
            setTimeout(() => { copyBtn.innerHTML = original; }, 1500);
        } catch (e) {
            alert('Could not copy to clipboard.');
        }
    });

    const showLoader = (message) => {
        if (!loader) return;
        const sub = loader.querySelector('.bds-regenerate-loader-sub');
        if (sub && message) sub.textContent = message;
        loader.setAttribute('aria-hidden', 'false');
        loader.classList.add('is-visible');
    };

    const hideLoader = () => {
        loader?.classList.remove('is-visible');
        loader?.setAttribute('aria-hidden', 'true');
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const mapContentTypeToCategory = (contentType) => {
        const type = String(contentType || '').toLowerCase();
        if (type.includes('reel')) return 'reel';
        if (type.includes('carousel')) return 'carousel';
        if (type.includes('image') || type.includes('caption')) return 'image';
        return 'caption';
    };

    const renderPromptList = (prompts) => {
        const list = document.getElementById('bds-prompt-list');
        if (!list) return;

        if (!prompts.length) {
            list.innerHTML = '<div class="bds-empty"><i class="ti ti-sparkles"></i><p>No AI prompts generated yet.</p></div>';
            return;
        }

        list.innerHTML = prompts.map((prompt) => {
            const meta = [
                prompt.platform ? `<span class="bds-kb-tag muted">${escapeHtml(prompt.platform.charAt(0).toUpperCase() + prompt.platform.slice(1))}</span>` : '',
                prompt.content_type ? `<span class="bds-kb-tag muted">${escapeHtml(String(prompt.content_type).replace(/_/g, ' '))}</span>` : '',
            ].filter(Boolean).join('');

            return `
                <div class="bds-prompt-item" data-prompt-id="${escapeHtml(prompt.id)}">
                    <i class="ti ti-wand bds-prompt-icon"></i>
                    <div class="bds-prompt-body">
                        <div class="bds-prompt-label">${escapeHtml(prompt.label)}</div>
                        <div class="bds-prompt-text">${escapeHtml(prompt.prompt_text)}</div>
                        ${meta ? `<div class="bds-prompt-meta">${meta}</div>` : ''}
                        <div class="bds-prompt-response" hidden></div>
                        <div class="bds-regenerate-actions">
                            <button type="button" class="btn btn-ghost btn-sm bds-prompt-regenerate-btn"
                                    data-prompt-text="${escapeHtml(prompt.prompt_text)}"
                                    data-content-type="${escapeHtml(prompt.content_type || 'post')}">
                                <i class="ti ti-refresh"></i> Regenerate
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        bindPromptRegenerateButtons();
    };

    const updateKnowledgeBaseUi = (payload) => {
        const ai = payload.ai_analysis || {};
        const kb = payload.knowledge_base || {};

        document.querySelectorAll('.bds-ai-response-text').forEach((el) => {
            if (ai.brand_summary) el.textContent = ai.brand_summary;
        });

        const jsonPre = document.getElementById('bds-ai-response-json');
        if (jsonPre) {
            jsonPre.textContent = JSON.stringify(ai, null, 2);
        }

        const toneEl = document.querySelector('[data-kb-field="detected_tone"]');
        const audienceEl = document.querySelector('[data-kb-field="detected_audience"]');
        const servicesEl = document.querySelector('[data-kb-field="detected_services"]');
        const trainedEl = document.querySelector('[data-kb-field="last_trained_at"]');

        if (toneEl) toneEl.textContent = kb.detected_tone || ai.detected_tone || '—';
        if (audienceEl) audienceEl.textContent = kb.detected_audience || ai.detected_audience || '—';
        if (servicesEl) servicesEl.textContent = kb.detected_services || ai.detected_services || '—';
        if (trainedEl && kb.last_trained_at) trainedEl.textContent = kb.last_trained_at;

        renderPromptList(payload.suggested_prompts || []);
    };

    const regenerateKnowledgeBase = async () => {
        showLoader('Groq / AI se naya brand summary aur prompts generate ho rahe hain');

        try {
            const response = await fetch(regenerateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Regeneration failed. Please try again.');
            }

            updateKnowledgeBaseUi(payload);
            window.location.reload();
        } catch (error) {
            alert(error.message || 'Regeneration failed. Please try again.');
        } finally {
            hideLoader();
        }
    };

    const setButtonLoading = (button, loading, loadingLabel = 'Regenerating…') => {
        if (!button) return;
        if (loading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<i class="ti ti-loader"></i> ${loadingLabel}`;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalHtml || '<i class="ti ti-refresh"></i> Regenerate';
        }
    };

    const showPromptResponse = (container, response, provider, model) => {
        if (!container) return;
        container.hidden = false;
        container.innerHTML = `
            <div class="bds-prompt-response-label"><i class="ti ti-sparkles"></i> AI Response</div>
            <div class="bds-prompt-response-text">${escapeHtml(response)}</div>
            ${provider ? `<div class="bds-prompt-response-meta">${escapeHtml(provider)}${model ? ' · ' + escapeHtml(model) : ''}</div>` : ''}
        `;
    };

    const regenerateSinglePrompt = async (button) => {
        const item = button.closest('.bds-prompt-item');
        const promptText = button.dataset.promptText || item?.querySelector('.bds-prompt-text')?.textContent?.trim() || '';
        const label = item?.querySelector('.bds-prompt-label')?.textContent?.trim() || 'Suggested prompt';
        const category = mapContentTypeToCategory(button.dataset.contentType);
        const responseBox = item?.querySelector('.bds-prompt-response');

        if (!promptText) {
            alert('Prompt text missing.');
            return;
        }

        setButtonLoading(button, true);

        try {
            const response = await fetch(promptRegenerateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    category,
                    title: label,
                    text: promptText,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Could not regenerate this prompt.');
            }

            showPromptResponse(responseBox, payload.response, payload.provider, payload.model);
        } catch (error) {
            alert(error.message || 'Could not regenerate this prompt.');
        } finally {
            setButtonLoading(button, false);
        }
    };

    const bindPromptRegenerateButtons = () => {
        document.querySelectorAll('.bds-prompt-regenerate-btn').forEach((button) => {
            button.addEventListener('click', () => regenerateSinglePrompt(button));
        });
    };

    ['bds-regenerate-hero', 'bds-regenerate-kb', 'bds-regenerate-prompts', 'bds-regenerate-prompts-empty'].forEach((id) => {
        document.getElementById(id)?.addEventListener('click', regenerateKnowledgeBase);
    });

    bindPromptRegenerateButtons();
});
</script>
@endpush
@endif
