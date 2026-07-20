@php
    $kb = $profile['knowledge_base'] ?? null;
    $kbReady = $kb?->training_status === 'complete';
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

<div class="bds-kb-grid bds-kb-grid-full">
    <div class="bds-card bds-kb-card">
        <div class="bds-card-head">
            <span class="bds-step-num bds-step-num-ai"><i class="ti ti-cpu"></i></span>
            <div>
                <div class="bds-card-title">AI Knowledge Base</div>
                <div class="bds-card-sub">CMO AI uses this to generate on-brand content</div>
            </div>
            @if ($kb)
                @if ($kbReady)
                    <span class="badge badge-green">Ready</span>
                @else
                    <span class="badge" style="background:var(--purple-lt);color:var(--purple2)">Learning</span>
                @endif
            @endif
        </div>

        @if ($kb)
            <div class="bds-ai-status-bar {{ $aiLive ? 'is-live' : ($kbReady ? 'is-fallback' : 'is-learning') }}">
                <div class="bds-ai-status-main">
                    @if ($aiLive)
                        <span class="bds-ai-provider-badge groq"><i class="ti ti-bolt"></i> {{ $aiProviderLabel }} response received</span>
                    @elseif ($kbReady)
                        <span class="bds-ai-provider-badge local"><i class="ti ti-alert-triangle"></i> {{ $aiProviderLabel }} fallback — external AI not used</span>
                    @else
                        <span class="bds-ai-provider-badge learning"><i class="ti ti-loader"></i> Waiting for AI response</span>
                    @endif
                    @if ($aiModel)
                        <span class="bds-ai-model-tag">{{ $aiModel }}</span>
                    @endif
                </div>
                <div class="bds-ai-status-meta">
                    <span title="Brand created">Created {{ $brand->created_at?->format('M j, Y g:i A') }}</span>
                    @if ($brand->sources_updated_at)
                        <span title="Sources last updated"> · Updated {{ $brand->sources_updated_at->format('M j, Y g:i A') }}</span>
                    @endif
                    @if ($kb->last_trained_at)
                        <span title="AI training"> · Last trained {{ $kb->last_trained_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
            </div>

            @if ($kbReady && ! $aiLive)
                <p class="bds-ai-status-hint"><i class="ti ti-info-circle"></i> Check <code>BLUESMINDS_API_KEY</code> in <code>.env</code> and <code>storage/logs/laravel.log</code>, then update data sources to retrain.</p>
            @endif

            @if (! empty($ai['brand_summary']))
                <div class="bds-kb-block bds-ai-response-hero">
                    <div class="bds-info-label"><i class="ti ti-message-chatbot"></i> Ai response — brand summary</div>
                    <div class="bds-kb-text bds-ai-response-text">{{ $ai['brand_summary'] }}</div>
                </div>
            @endif

            @if (! empty($ai['content_strategy']['themes']) || ! empty($ai['content_strategy']['pillars']))
                <div class="bds-kb-block bds-ai-response-hero">
                    <div class="bds-info-label"><i class="ti ti-chart-dots"></i> Ai content strategy</div>
                    @if (! empty($ai['content_strategy']['themes']))
                        <div class="bds-kb-tags">
                            @foreach ($ai['content_strategy']['themes'] as $theme)
                                <span class="bds-kb-tag">{{ $theme }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if (! empty($ai['content_strategy']['pillars']))
                        <div class="bds-kb-text" style="margin-top:8px">{{ implode(' · ', $ai['content_strategy']['pillars']) }}</div>
                    @endif
                </div>
            @endif

            @php
                $keywords = $kb->top_keywords ?? ($ai['top_keywords'] ?? []);
            @endphp
            @if (! empty($keywords))
                <div class="bds-kb-block">
                    <div class="bds-info-label">Top keywords (Groq)</div>
                    <div class="bds-kb-tags">
                        @foreach ($keywords as $keyword)
                            <span class="bds-kb-tag muted">#{{ ltrim($keyword, '#') }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bds-info-grid">
                <div class="bds-info-field">
                    <div class="bds-info-label">Status</div>
                    <div class="bds-info-value">
                        @if ($kbReady)
                            <span class="badge badge-green">Ready</span>
                        @else
                            <span class="badge" style="background:var(--purple-lt);color:var(--purple2)">Learning</span>
                        @endif
                    </div>
                </div>
                <div class="bds-info-field">
                    <div class="bds-info-label">Last trained</div>
                    <div class="bds-info-value" data-kb-field="last_trained_at">{{ $kb->last_trained_at?->format('M j, Y g:i A') ?? '—' }}</div>
                </div>
                <div class="bds-info-field">
                    <div class="bds-info-label">Detected tone</div>
                    <div class="bds-info-value" data-kb-field="detected_tone">{{ $kb->detected_tone ?? ($ai['detected_tone'] ?? '—') }}</div>
                </div>
                <div class="bds-info-field">
                    <div class="bds-info-label">AI provider</div>
                    <div class="bds-info-value">
                        @if ($aiLive)
                            <span class="badge badge-green">{{ $aiProviderLabel }}</span>
                        @elseif ($aiProvider === 'local')
                            <span class="badge" style="background:var(--amber-lt,#FEF3C7);color:#B45309">Local</span>
                        @else
                            {{ ucfirst($aiProvider) }}
                        @endif
                    </div>
                </div>
                @if ($aiModel)
                <div class="bds-info-field">
                    <div class="bds-info-label">AI model</div>
                    <div class="bds-info-value"><code class="bds-ai-model-code">{{ $aiModel }}</code></div>
                </div>
                @endif
                @if (filled($kb->detected_audience ?? ($ai['detected_audience'] ?? null)))
                <div class="bds-info-field">
                    <div class="bds-info-label">Detected audience</div>
                    <div class="bds-info-value" data-kb-field="detected_audience">{{ $kb->detected_audience ?? $ai['detected_audience'] }}</div>
                </div>
                @endif
                @if (filled($kb->detected_services ?? ($ai['detected_services'] ?? null)))
                <div class="bds-info-field">
                    <div class="bds-info-label">Detected services</div>
                    <div class="bds-info-value" data-kb-field="detected_services">{{ $kb->detected_services ?? $ai['detected_services'] }}</div>
                </div>
                @endif
            </div>

            @if ($kbReady && ! empty($ai))
                <!-- <div class="bds-kb-block bds-ai-response-map">
                    <div class="bds-info-label">Ai Prompt Response</div>
                    <ul class="bds-ai-response-list">
                        <li><strong>AI brand summary</strong> — Groq ne brand ke baare mein likha</li>
                        <li><strong>Tone / audience / services</strong> — Groq ne detect kiya</li>
                        <li><strong>Content themes & pillars</strong> — Groq ki content strategy</li>
                        <li><strong>AI Prompt Center</strong> (right card) — Groq ke <code>suggested_prompts</code></li>
                        <li><strong>Content Suggestions</strong> page — Groq data + ready prompts</li>
                    </ul>
                    <div class="bds-regenerate-actions">
                        <button type="button" class="btn btn-ghost btn-sm bds-regenerate-btn" id="bds-regenerate-kb">
                            <i class="ti ti-refresh"></i> Regenerate
                        </button>
                    </div>
                </div> -->

                <details class="bds-ai-response-details">
                    <summary class="bds-ai-response-summary">
                        <i class="ti ti-code"></i> AI response Full (JSON)
                    </summary>
                    <div class="bds-ai-response-panel">
                        <div class="bds-ai-response-actions">
                            <button type="button" class="btn btn-ghost btn-sm" id="bds-copy-ai-response">
                                <i class="ti ti-copy"></i> Copy JSON
                            </button>
                        </div>
                        <pre class="bds-ai-response-json" id="bds-ai-response-json">{{ json_encode($ai, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </details>
            @endif

            @if ($kb->training_error && ! $kbReady)
                <p class="bds-kb-note"><i class="ti ti-info-circle"></i> CMO AI is still learning your brand — this updates automatically.</p>
            @endif
        @else
            <div class="bds-empty">
                <i class="ti ti-cpu"></i>
                <p>AI hasn't analyzed your brand yet.</p>
                <p class="bds-empty-sub">Add data sources and CMO AI will build your knowledge base.</p>
                <a href="{{ route('app.brand.data-sources') }}" class="btn btn-ghost btn-sm">Go to data sources</a>
            </div>
        @endif
    </div>

    {{-- AI Prompt Center — hidden per request
    <div class="bds-card bds-kb-card">
        <div class="bds-card-head">
            <span class="bds-step-num bds-step-num-prompt"><i class="ti ti-sparkles"></i></span>
            <div>
                <div class="bds-card-title">AI Prompt Center</div>
                <div class="bds-card-sub">Ready-to-use prompts generated from your brand data</div>
            </div>
            <span class="bds-count-badge">{{ ($profile['suggested_prompts'] ?? collect())->count() }}</span>
        </div>

        @if (($profile['suggested_prompts'] ?? collect())->isNotEmpty())
            <div class="bds-prompt-list" id="bds-prompt-list">
                @foreach ($profile['suggested_prompts'] as $prompt)
                    <div class="bds-prompt-item" data-prompt-id="{{ $prompt->id }}">
                        <i class="ti ti-wand bds-prompt-icon"></i>
                        <div class="bds-prompt-body">
                            <div class="bds-prompt-label">{{ $prompt->label }}</div>
                            <div class="bds-prompt-text">{{ $prompt->prompt_text }}</div>
                            @if ($prompt->platform || $prompt->content_type)
                                <div class="bds-prompt-meta">
                                    @if ($prompt->platform)
                                        <span class="bds-kb-tag muted">{{ ucfirst($prompt->platform) }}</span>
                                    @endif
                                    @if ($prompt->content_type)
                                        <span class="bds-kb-tag muted">{{ str_replace('_', ' ', $prompt->content_type) }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="bds-prompt-response" hidden></div>
                            <div class="bds-regenerate-actions">
                                <button type="button" class="btn btn-ghost btn-sm bds-prompt-regenerate-btn"
                                        data-prompt-text="{{ $prompt->prompt_text }}"
                                        data-content-type="{{ $prompt->content_type ?? 'post' }}">
                                    <i class="ti ti-refresh"></i> Regenerate
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="bds-regenerate-actions bds-regenerate-actions-footer">
                <button type="button" class="btn btn-ghost btn-sm bds-regenerate-btn" id="bds-regenerate-prompts">
                    <i class="ti ti-refresh"></i> Regenerate all prompts
                </button>
            </div>
        @else
            <div class="bds-empty">
                <i class="ti ti-sparkles"></i>
                <p>No AI prompts generated yet.</p>
                <p class="bds-empty-sub">Prompts appear here after CMO AI learns your brand.</p>
                @if ($kb)
                    <div class="bds-regenerate-actions" style="justify-content:center;margin-top:12px">
                        <button type="button" class="btn btn-ghost btn-sm bds-regenerate-btn" id="bds-regenerate-prompts-empty">
                            <i class="ti ti-refresh"></i> Regenerate
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
    --}}
</div>
