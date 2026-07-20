@extends('layouts.app')

@section('title', 'Brand Content Suggestions — '.$brand->name)
@section('pageTitle', 'Brand Content Suggestions')

@section('topbarExtra')
    <a href="{{ route('app.brand.knowledge-base') }}" class="btn btn-ghost btn-sm"><i class="ti ti-brain"></i> Knowledge base</a>
@endsection

<style>
    .bcs-grid {
    display: grid;
    grid-template-columns: repeat({{ max(1, ($featureLocked ?? ! ($canAccessContentSuggestions ?? true)) ? 1 : count($categories ?? [])) }},minmax(0,1fr)) !important;
    gap: 16px;
    align-items: start;
}
</style>

@section('content')
<div class="bcs-page">
    <div class="bds-hero">
        <div class="bds-hero-main">
            <div class="bds-hero-icon" style="background:var(--purple)"><i class="ti ti-bulb"></i></div>
            <div>
                <h1 class="bds-hero-title">Brand Content Suggestions</h1>
                <p class="bds-hero-sub">AI prompts generated from your Brand Knowledge Base — ready to copy and use</p>
            </div>
        </div>
        <div class="bds-hero-status">
            @if ($kbReady)
                <span class="bds-status-pill ready"><i class="ti ti-check"></i> Based on trained KB</span>
            @else
                <span class="bds-status-pill learning"><i class="ti ti-loader"></i> Using brand data</span>
            @endif
            {{-- Copy all — hidden per request
            <button type="button" class="btn btn-ghost btn-sm" id="bcs-copy-all"><i class="ti ti-copy"></i> Copy all</button>
            --}}
        </div>
    </div>

    @if ($featureLocked ?? ! ($canAccessContentSuggestions ?? true))
        @include('app.brand.partials.plan-upgrade')
    @else
    <form method="POST" action="{{ route('app.brand.content-suggestions.generate') }}" id="bcs-generate-form">
        @csrf
        <div id="bcs-generate-inputs"></div>
    </form>

    <div class="bcs-toolbar">
        <div class="bcs-toolbar-left">
            <label class="bcs-select-all">
                <input type="checkbox" id="bcs-select-all">
                <span>Select all</span>
            </label>
            <span class="bcs-selected-pill" id="bcs-selected-count">0 selected</span>
        </div>
        <div class="bcs-toolbar-right">
            <button type="button" class="bcs-tab" id="bcs-regenerate-all">
                <i class="ti ti-refresh"></i> Regenerate Prompts
            </button>
            <button type="button" class="bcs-tab active" id="bcs-generated-ai-btn">
                <i class="ti ti-sparkles"></i> <span class="bcs-btn-label">Generate Content</span>
            </button>
        </div>
    </div>

    <div class="bcs-grid">
        @foreach ($categories as $category)
            <div class="bcs-column card">
                <div class="bcs-column-head">
                    <div class="bcs-column-icon" style="background:{{ $category['bg'] }}">
                        <i class="ti {{ $category['icon'] }}" style="color:{{ $category['color'] }}"></i>
                    </div>
                    <div class="bcs-column-title">{{ $category['title'] }}</div>
                    <span class="bds-count-badge">{{ count($category['prompts']) }}</span>
                </div>

                <div class="bcs-prompt-list" data-category="{{ $category['key'] }}">
                    @foreach ($category['prompts'] as $prompt)
                        @php $promptId = $category['key'].'-'.$prompt['number']; @endphp
                        <div class="bcs-prompt-card" data-prompt-id="{{ $promptId }}"
                             data-prompt-text="{{ $prompt['text'] }}">
                            <div class="bcs-prompt-head">
                                <label class="bcs-prompt-check" title="Select prompt">
                                    <input type="checkbox" class="bcs-prompt-checkbox" value="{{ $promptId }}"
                                           data-category="{{ $category['key'] }}"
                                           data-title="{{ $prompt['title'] }}">
                                </label>
                                <button type="button" class="btn btn-ghost btn-sm bcs-copy-btn">
                                    <i class="ti ti-copy"></i> Copy
                                </button>
                            </div>
                            <div class="bcs-prompt-title">{{ $prompt['number'] }}. {{ $prompt['title'] }}</div>
                            <div class="bcs-prompt-body bcs-prompt-body-clickable" role="button" tabindex="0" title="Click to view full prompt">{{ $prompt['text'] }}</div>
                            {{-- Hashtags — hidden per request
                            @if (! empty($prompt['tags']))
                                <div class="bcs-prompt-tags">
                                    @foreach ($prompt['tags'] as $tag)
                                        <span class="bcs-prompt-tag">#{{ str_replace(' ', '', $tag) }}</span>
                                    @endforeach
                                </div>
                            @endif
                            --}}
                            <div class="bcs-prompt-response" hidden></div>
                            <div class="bcs-regenerate-actions">
                                <button type="button" class="btn btn-ghost btn-sm bcs-regenerate-btn"
                                        data-category="{{ $category['key'] }}"
                                        data-title="{{ $prompt['title'] }}">
                                    <i class="ti ti-refresh"></i> Regenerate
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>

@if (! ($featureLocked ?? ! ($canAccessContentSuggestions ?? true)))
<div class="modal-overlay" id="bcs-prompt-modal" aria-hidden="true">
    <div class="cmo-modal bcs-prompt-modal" role="dialog" aria-modal="true" aria-labelledby="bcs-prompt-modal-title">
        <div class="modal-head">
            <div>
                <h2 id="bcs-prompt-modal-title">Prompt</h2>
                <p id="bcs-prompt-modal-subtitle">Refreshed AI prompt</p>
            </div>
            <button type="button" class="close-btn" data-close-bcs-prompt-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
            <div class="bcs-prompt-modal-text" id="bcs-prompt-modal-text"></div>
        </div>
        <div class="modal-foot" style="justify-content:flex-end;gap:8px">
            <button type="button" class="btn btn-ghost" data-close-bcs-prompt-modal>Close</button>
            <button type="button" class="btn btn-green btn-sm" id="bcs-prompt-modal-copy"><i class="ti ti-copy"></i> Copy prompt</button>
        </div>
    </div>
</div>

<div class="bds-regenerate-loader" id="bcs-regenerate-loader" aria-hidden="true">
    <div class="bds-regenerate-loader-card">
        <span class="bds-regenerate-loader-ring"></span>
        <div class="bds-regenerate-loader-title" id="bcs-regenerate-loader-title">Regenerating AI response…</div>
        <p class="bds-regenerate-loader-sub" id="bcs-regenerate-loader-sub">Please wait while AI refreshes your prompt</p>
    </div>
</div>

<div class="bcs-gen-loader" id="bcs-gen-loader" aria-hidden="true" aria-live="polite">
    <div class="bcs-gen-loader-card">
        <div class="bcs-gen-loader-icon">
            <span class="bcs-gen-loader-ring"></span>
            <i class="ti ti-sparkles"></i>
        </div>
        <div class="bcs-gen-loader-title">Generating AI Content</div>
        <p class="bcs-gen-loader-sub" id="bcs-gen-loader-status">Preparing your selected prompts…</p>
        <div class="bcs-gen-loader-bar" aria-hidden="true">
            <div class="bcs-gen-loader-bar-fill" id="bcs-gen-loader-bar"></div>
        </div>
        <div class="bcs-gen-loader-meta">
            <span id="bcs-gen-loader-percent">0%</span>
            <span id="bcs-gen-loader-count">0 items</span>
        </div>
        <p class="bcs-gen-loader-tip">Creating captions, images, reels, and carousel slides — video reels may take 2–5 minutes.</p>
    </div>
</div>
@endif
@endsection

@if (! ($featureLocked ?? ! ($canAccessContentSuggestions ?? true)))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('bcs-select-all');
    const selectedCount = document.getElementById('bcs-selected-count');
    const generateForm = document.getElementById('bcs-generate-form');
    const generateInputsHost = document.getElementById('bcs-generate-inputs');
    const generatedAiBtn = document.getElementById('bcs-generated-ai-btn');
    const loader = document.getElementById('bcs-gen-loader');
    const loaderStatus = document.getElementById('bcs-gen-loader-status');
    const loaderBar = document.getElementById('bcs-gen-loader-bar');
    const loaderPercent = document.getElementById('bcs-gen-loader-percent');
    const loaderCount = document.getElementById('bcs-gen-loader-count');
    const checkboxes = () => [...document.querySelectorAll('.bcs-prompt-checkbox')];
    const checkedBoxes = () => checkboxes().filter((cb) => cb.checked);

    let progressTimer = null;
    let progressValue = 0;

    const categoryLabels = {
        caption: 'captions',
        image: 'images',
        reel: 'reel videos',
        carousel: 'carousel slides',
    };

    const estimateDuration = (selected) => {
        const weights = { caption: 12, image: 35, reel: 240, carousel: 120 };
        const seconds = selected.reduce((total, checkbox) => {
            const category = checkbox.dataset.category || 'caption';
            return total + (weights[category] || 15);
        }, 8);

        return Math.min(Math.max(seconds * 1000, 20000), 900000);
    };

    const buildStatusMessages = (selected) => {
        const categories = [...new Set(selected.map((cb) => cb.dataset.category || 'caption'))];
        const messages = ['Analyzing your brand prompts…', 'Connecting to AI models…'];

        categories.forEach((category) => {
            messages.push(`Generating ${categoryLabels[category] || 'content'}…`);
        });

        messages.push('Saving to Content Library…', 'Finalizing your content…');

        return messages;
    };

    const stopProgress = () => {
        if (progressTimer) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
    };

    const setProgress = (value) => {
        progressValue = Math.max(0, Math.min(100, Math.round(value)));
        if (loaderBar) loaderBar.style.width = `${progressValue}%`;
        if (loaderPercent) loaderPercent.textContent = `${progressValue}%`;
    };

    const showLoader = (selected) => {
        if (!loader) return;

        const messages = buildStatusMessages(selected);
        let messageIndex = 0;

        loader.setAttribute('aria-hidden', 'false');
        loader.classList.add('is-visible');
        if (loaderCount) loaderCount.textContent = `${selected.length} item${selected.length === 1 ? '' : 's'}`;
        setProgress(0);
        if (loaderStatus) loaderStatus.textContent = messages[0];

        const duration = estimateDuration(selected);
        const startedAt = Date.now();

        stopProgress();
        progressTimer = setInterval(() => {
            const elapsed = Date.now() - startedAt;
            const ratio = Math.min(elapsed / duration, 1);
            const eased = 8 + (ratio * 82);
            setProgress(eased);

            const nextIndex = Math.min(
                messages.length - 1,
                Math.floor(ratio * messages.length),
            );

            if (nextIndex !== messageIndex && loaderStatus) {
                messageIndex = nextIndex;
                loaderStatus.textContent = messages[messageIndex];
            }
        }, 180);
    };

    const finishLoader = (redirectUrl, selected = []) => {
        stopProgress();
        setProgress(100);
        if (loaderStatus) loaderStatus.textContent = 'Complete! Opening Content Library…';
        loader?.classList.add('is-complete');
        hideUsedPromptCards(selected);

        window.setTimeout(() => {
            window.location.href = redirectUrl;
        }, 700);
    };

    const hideUsedPromptCards = (selected) => {
        selected.forEach((checkbox) => {
            const card = checkbox.closest('.bcs-prompt-card');
            if (!card) return;
            card.classList.add('is-used');
            window.setTimeout(() => card.remove(), 250);
        });
        syncSelectionUi();
    };

    const hideLoader = () => {
        stopProgress();
        loader?.classList.remove('is-visible', 'is-complete');
        loader?.setAttribute('aria-hidden', 'true');
        setProgress(0);
    };

    const resetGenerateButton = () => {
        const label = generatedAiBtn?.querySelector('.bcs-btn-label');
        if (label) label.textContent = 'Generated Content';
        if (generatedAiBtn) generatedAiBtn.disabled = false;
    };

    const syncSelectionUi = () => {
        const checked = checkedBoxes();
        const total = checkboxes().length;

        if (selectedCount) {
            selectedCount.textContent = `${checked.length} selected`;
        }

        if (selectAll) {
            selectAll.indeterminate = checked.length > 0 && checked.length < total;
            selectAll.checked = total > 0 && checked.length === total;
        }

        if (generatedAiBtn) {
            generatedAiBtn.classList.toggle('is-ready', checked.length > 0);
        }
    };

    selectAll?.addEventListener('change', () => {
        const checked = selectAll.checked;
        checkboxes().forEach((cb) => {
            cb.checked = checked;
            cb.closest('.bcs-prompt-card')?.classList.toggle('is-selected', checked);
        });
        syncSelectionUi();
    });

    document.querySelectorAll('.bcs-prompt-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            checkbox.closest('.bcs-prompt-card')?.classList.toggle('is-selected', checkbox.checked);
            syncSelectionUi();
        });
    });

    generatedAiBtn?.addEventListener('click', async () => {
        const selected = checkedBoxes();
        if (!selected.length) {
            alert('Select at least one content suggestion.');
            return;
        }

        if (!generateForm || !generateInputsHost) {
            return;
        }

        generateInputsHost.innerHTML = '';

        selected.forEach((checkbox, index) => {
            const card = checkbox.closest('.bcs-prompt-card');
            const fields = {
                category: checkbox.dataset.category || 'caption',
                title: checkbox.dataset.title || 'Content idea',
                text: card?.querySelector('.bcs-prompt-body')?.textContent?.trim() || '',
            };

            Object.entries(fields).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `prompts[${index}][${key}]`;
                input.value = value;
                generateInputsHost.appendChild(input);
            });
        });

        const label = generatedAiBtn.querySelector('.bcs-btn-label');
        if (label) label.textContent = 'Generating…';
        generatedAiBtn.disabled = true;
        showLoader(selected);

        try {
            const formData = new FormData(generateForm);
            const response = await fetch(generateForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));
            let errorMessage = payload.message;

            if (! errorMessage && payload.errors) {
                errorMessage = Object.values(payload.errors).flat()[0];
            }

            if (!response.ok) {
                throw new Error(errorMessage || 'AI generation failed. Please try again.');
            }

            finishLoader(payload.redirect || '{{ route('app.brand.content-library', ['tab' => 'ai']) }}', selected);
        } catch (error) {
            hideLoader();
            resetGenerateButton();
            alert(error.message || 'AI generation failed. Please try again.');
        }
    });

    syncSelectionUi();

    const copyText = async (text, btn) => {
        try {
            await navigator.clipboard.writeText(text);
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check"></i> Copied';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        } catch (e) {
            alert('Could not copy to clipboard.');
        }
    };

    document.querySelectorAll('.bcs-copy-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const text = btn.closest('.bcs-prompt-card')?.querySelector('.bcs-prompt-body')?.textContent?.trim() || '';
            copyText(text, btn);
        });
    });

    document.getElementById('bcs-copy-all')?.addEventListener('click', () => {
        const checked = checkedBoxes();
        const source = checked.length
            ? checked.map((cb) => cb.closest('.bcs-prompt-card')?.querySelector('.bcs-prompt-body'))
            : [...document.querySelectorAll('.bcs-prompt-body')];
        const texts = source.map((el) => el?.textContent?.trim() || '').filter(Boolean);
        copyText(texts.join('\n\n---\n\n'), document.getElementById('bcs-copy-all'));
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const kbRegenerateUrl = @json(route('app.brand.knowledge-base.regenerate'));
    const promptRegenerateUrl = @json(route('app.brand.content-suggestions.regenerate-prompt'));

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const setRegenerateLoading = (button, loading) => {
        if (!button) return;
        if (loading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="ti ti-loader"></i> Regenerating…';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalHtml || '<i class="ti ti-refresh"></i> Regenerate';
        }
    };

    const showCardResponse = (card, response, provider, model) => {
        const box = card?.querySelector('.bcs-prompt-response');
        if (!box) return;
        box.hidden = false;
        box.innerHTML = `
            <div class="bcs-prompt-response-label"><i class="ti ti-sparkles"></i> AI Response</div>
            <div class="bcs-prompt-response-text">${escapeHtml(response)}</div>
            ${provider ? `<div class="bcs-prompt-response-meta">${escapeHtml(provider)}${model ? ' · ' + escapeHtml(model) : ''}</div>` : ''}
        `;
    };

    const regenerateLoader = document.getElementById('bcs-regenerate-loader');
    const regenerateLoaderTitle = document.getElementById('bcs-regenerate-loader-title');
    const regenerateLoaderSub = document.getElementById('bcs-regenerate-loader-sub');

    const showRegenerateLoader = (title = 'Regenerating AI response…', subtitle = '') => {
        if (!regenerateLoader) return;
        if (regenerateLoaderTitle) regenerateLoaderTitle.textContent = title;
        if (regenerateLoaderSub) regenerateLoaderSub.textContent = subtitle;
        regenerateLoader.setAttribute('aria-hidden', 'false');
        regenerateLoader.classList.add('is-visible');
    };

    const hideRegenerateLoader = () => {
        regenerateLoader?.classList.remove('is-visible');
        regenerateLoader?.setAttribute('aria-hidden', 'true');
    };

    const promptModal = document.getElementById('bcs-prompt-modal');
    const promptModalTitle = document.getElementById('bcs-prompt-modal-title');
    const promptModalSubtitle = document.getElementById('bcs-prompt-modal-subtitle');
    const promptModalText = document.getElementById('bcs-prompt-modal-text');
    const promptModalCopy = document.getElementById('bcs-prompt-modal-copy');
    let promptModalCurrentText = '';

    const openPromptModal = (title, prompt, subtitle = 'AI generated prompt') => {
        if (!promptModal) return;
        promptModalCurrentText = prompt || '';
        if (promptModalTitle) promptModalTitle.textContent = title || 'Prompt';
        if (promptModalSubtitle) promptModalSubtitle.textContent = subtitle;
        if (promptModalText) promptModalText.textContent = promptModalCurrentText;
        promptModal.classList.add('is-open');
        promptModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closePromptModal = () => {
        if (!promptModal) return;
        promptModal.classList.remove('is-open');
        promptModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    promptModal?.querySelectorAll('[data-close-bcs-prompt-modal]').forEach((btn) => {
        btn.addEventListener('click', closePromptModal);
    });

    promptModal?.addEventListener('click', (event) => {
        if (event.target === promptModal) closePromptModal();
    });

    promptModalCopy?.addEventListener('click', () => {
        copyText(promptModalCurrentText, promptModalCopy);
    });

    document.querySelectorAll('.bcs-prompt-body-clickable').forEach((body) => {
        const openFromCard = () => {
            const card = body.closest('.bcs-prompt-card');
            const title = card?.querySelector('.bcs-prompt-title')?.textContent?.trim() || 'Prompt';
            const text = card?.dataset.promptText?.trim() || body.textContent?.trim() || '';
            openPromptModal(title, text, 'View full prompt');
        };

        body.addEventListener('click', openFromCard);
        body.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openFromCard();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && promptModal?.classList.contains('is-open')) {
            closePromptModal();
        }
    });

    const updateCardPrompt = (card, prompt) => {
        const body = card?.querySelector('.bcs-prompt-body');
        if (body) {
            body.textContent = prompt;
            body.classList.add('is-refreshed');
            window.setTimeout(() => body.classList.remove('is-refreshed'), 1200);
        }
        if (card) {
            card.dataset.promptText = prompt;
        }
        card?.querySelector('.bcs-prompt-response')?.setAttribute('hidden', '');
    };

    const regenerateCard = async (button) => {
        const card = button.closest('.bcs-prompt-card');
        const promptText = card?.dataset.promptText?.trim()
            || card?.querySelector('.bcs-prompt-body')?.textContent?.trim()
            || '';

        if (!promptText) {
            alert('Prompt text missing.');
            return;
        }

        showRegenerateLoader(
            'Regenerating prompt…',
            // 'AI se naya prompt generate ho raha hai — thodi der rukiye',
        );
        setRegenerateLoading(button, true);

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
                    category: button.dataset.category || 'caption',
                    title: button.dataset.title || 'Content idea',
                    text: promptText,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Could not regenerate this prompt.');
            }

            const newPrompt = payload.prompt || payload.response || '';
            updateCardPrompt(card, newPrompt);

            const title = card?.querySelector('.bcs-prompt-title')?.textContent?.trim() || button.dataset.title || 'Prompt';
            openPromptModal(title, newPrompt, 'Refreshed AI prompt');
        } catch (error) {
            alert(error.message || 'Could not regenerate this prompt.');
        } finally {
            hideRegenerateLoader();
            setRegenerateLoading(button, false);
        }
    };

    document.querySelectorAll('.bcs-regenerate-btn').forEach((button) => {
        button.addEventListener('click', () => regenerateCard(button));
    });

    document.getElementById('bcs-regenerate-all')?.addEventListener('click', async () => {
        const button = document.getElementById('bcs-regenerate-all');
        showRegenerateLoader(
            'Regenerating AI response…',
            // 'Groq / AI se naya brand summary aur prompts generate ho rahe hain',
        );
        if (button) button.disabled = true;

        try {
            const response = await fetch(kbRegenerateUrl, {
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
                throw new Error(payload.message || 'Regeneration failed.');
            }

            window.location.reload();
        } catch (error) {
            hideRegenerateLoader();
            if (button) button.disabled = false;
            alert(error.message || 'Regeneration failed.');
        }
    });
});
</script>
@endpush
@endif
