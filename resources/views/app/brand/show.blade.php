@extends('layouts.app')

@section('title', $brand->name.' — Brand profile')
@section('pageTitle', 'Brand profile')

@section('content')
<div style="margin-bottom:16px">
    <a href="{{ route('app.brands.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Back to brands</a>
</div>

<div class="welcome-bar" style="margin-bottom:20px">
    <div>
        <div class="wb-h">{{ $brand->name }}</div>
        <div class="wb-p">
            @if ($brand->setup_completed_at)
                Setup complete · {{ $brand->setup_completed_at->format('M j, Y') }}
            @else
                Setup in progress · Step {{ $brand->setup_step ?? 1 }} of 5
            @endif
        </div>
    </div>
</div>

@if ($profile['knowledge_base'])
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num" style="background:var(--green)"><i class="ti ti-cpu" style="font-size:14px"></i></span>
        <div>
            <div class="brand-step-title">AI Knowledge Base</div>
            <div class="brand-step-sub">CMO AI uses this to generate on-brand content</div>
        </div>
    </div>
    @php $kb = $profile['knowledge_base']; @endphp
    <div class="profile-grid">
        <div class="profile-field">
            <div class="profile-label">Status</div>
            <div class="profile-value">
                @if ($kb->training_status === 'complete')
                    <span class="badge badge-green">Ready</span>
                @else
                    <span class="badge" style="background:var(--purple-lt);color:var(--purple2)">Learning</span>
                @endif
            </div>
        </div>
        <div class="profile-field">
            <div class="profile-label">Last trained</div>
            <div class="profile-value">{{ $kb->last_trained_at?->format('M j, Y g:i A') ?? '—' }}</div>
        </div>
        <div class="profile-field">
            <div class="profile-label">Detected tone</div>
            <div class="profile-value">{{ $kb->detected_tone ?? '—' }}</div>
        </div>
        <div class="profile-field">
            <div class="profile-label">AI provider</div>
            <div class="profile-value">{{ ucfirst($profile['ai_analysis']['provider'] ?? 'local') }}</div>
        </div>
    </div>
    @if (! empty($profile['ai_analysis']['brand_summary']))
        <div class="profile-field" style="margin-top:8px">
            <div class="profile-label">AI brand summary</div>
            <div class="profile-value">{{ $profile['ai_analysis']['brand_summary'] }}</div>
        </div>
    @endif
    @if (! empty($profile['ai_analysis']['content_strategy']['themes']))
        <div class="profile-field" style="margin-top:8px">
            <div class="profile-label">Content strategy themes</div>
            <div class="profile-value">{{ implode(' · ', $profile['ai_analysis']['content_strategy']['themes']) }}</div>
        </div>
    @endif
    @if ($kb->training_error && $kb->training_status !== 'complete')
        <p class="profile-empty">CMO AI will finish learning automatically — no action needed.</p>
    @endif
</div>
@endif

@if (($profile['suggested_prompts'] ?? collect())->isNotEmpty())
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num" style="background:var(--purple)"><i class="ti ti-sparkles" style="font-size:14px"></i></span>
        <div>
            <div class="brand-step-title">AI Prompt Center</div>
            <div class="brand-step-sub">Ready-to-use prompts generated from your brand data</div>
        </div>
    </div>
    @foreach ($profile['suggested_prompts'] as $prompt)
        <div class="q-item" style="align-items:flex-start">
            <i class="ti ti-wand" style="font-size:18px;margin-top:2px;color:var(--purple2)"></i>
            <span class="q-title" style="flex:1">
                <strong>{{ $prompt->label }}</strong>
                <span style="display:block;font-size:12px;color:var(--text2);font-weight:400;margin-top:4px;line-height:1.5">{{ $prompt->prompt_text }}</span>
            </span>
        </div>
    @endforeach
</div>
@endif

{{-- Step 1 --}}
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num">1</span>
        <div>
            <div class="brand-step-title">{{ $profile['step1']['title'] }}</div>
            <div class="brand-step-sub">Description, products, services, audience</div>
        </div>
    </div>
    @if (count($profile['step1']['fields']))
        <div class="profile-grid">
            @foreach ($profile['step1']['fields'] as $field)
                <div class="profile-field">
                    <div class="profile-label">{{ $field['label'] }}</div>
                    <div class="profile-value">{{ $field['value'] }}</div>
                </div>
            @endforeach
        </div>
    @else
        <p class="profile-empty">No business information saved yet.</p>
    @endif
</div>

{{-- Step 2 --}}
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num">2</span>
        <div>
            <div class="brand-step-title">{{ $profile['step2']['title'] }}</div>
            <div class="brand-step-sub">Logo, images, PDFs, brand guidelines</div>
        </div>
    </div>
    @if (count($profile['step2']['assets']))
        <div class="uploaded-files">
            @foreach ($profile['step2']['assets'] as $asset)
                <div class="file-row">
                    <i class="ti ti-file fi" style="color:var(--purple2)"></i>
                    <span class="fn">{{ $asset['name'] }}</span>
                    <span class="fs">{{ $asset['size'] }}</span>
                    <i class="ti ti-check fck"></i>
                </div>
            @endforeach
        </div>
    @else
        <p class="profile-empty">No brand assets uploaded yet.</p>
    @endif
</div>

{{-- Step 3 --}}
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num">3</span>
        <div>
            <div class="brand-step-title">{{ $profile['step3']['title'] }}</div>
            <div class="brand-step-sub">Brand website and social media URLs</div>
        </div>
    </div>
    @if ($profile['step3']['website'] || count($profile['step3']['social_urls']))
        @if ($profile['step3']['website'])
            <div class="profile-field" style="margin-bottom:14px">
                <div class="profile-label">Brand website</div>
                <div class="profile-value"><a href="{{ $profile['step3']['website'] }}" target="_blank" rel="noopener">{{ $profile['step3']['website'] }}</a></div>
            </div>
        @endif
        @if (count($profile['step3']['social_urls']))
            <div class="profile-label" style="margin-bottom:8px">Brand Social Media</div>
            <div class="profile-grid">
                @foreach ($profile['step3']['social_urls'] as $social)
                    <div class="profile-field">
                        <div class="profile-label">{{ $social['label'] }}</div>
                        <div class="profile-value"><a href="{{ $social['url'] }}" target="_blank" rel="noopener">{{ $social['url'] }}</a></div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <p class="profile-empty">No brand URLs saved yet.</p>
    @endif
</div>

{{-- Step 4 --}}
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num">4</span>
        <div>
            <div class="brand-step-title">{{ $profile['step4']['title'] }}</div>
            <div class="brand-step-sub">Reference links for CMO AI</div>
        </div>
    </div>
    @if (count($profile['step4']['urls']))
        <div class="profile-url-list">
            @foreach ($profile['step4']['urls'] as $index => $url)
                <div class="profile-field">
                    <div class="profile-label">Reference URL {{ $index + 1 }}</div>
                    <div class="profile-value"><a href="{{ $url }}" target="_blank" rel="noopener">{{ $url }}</a></div>
                </div>
            @endforeach
        </div>
    @else
        <p class="profile-empty">No reference URLs added yet.</p>
    @endif
</div>

{{-- Step 5 --}}
<div class="card brand-step-card">
    <div class="brand-step-head">
        <span class="brand-step-num">5</span>
        <div>
            <div class="brand-step-title">{{ $profile['step5']['title'] }}</div>
            <div class="brand-step-sub">Connected social media accounts</div>
        </div>
    </div>
    @if ($profile['step5']['accounts']->isNotEmpty())
        @foreach ($profile['step5']['accounts'] as $account)
            <div class="q-item">
                <i class="ti ti-brand-{{ $account->platform === 'x' ? 'x' : $account->platform }}" style="font-size:18px"></i>
                <span class="q-title">{{ $account->account_name ?? ucfirst($account->platform) }}</span>
                <span class="badge {{ $account->status === 'active' ? 'badge-green' : '' }}">{{ ucfirst($account->status) }}</span>
            </div>
        @endforeach
    @else
        <p class="profile-empty">No social accounts connected yet.</p>
    @endif
</div>
@endsection
