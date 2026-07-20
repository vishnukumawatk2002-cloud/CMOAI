@extends('layouts.app')

@section('title', 'Social accounts — '.$brand->name)
@section('pageTitle', 'Social accounts')

@section('topbarExtra')
    <button type="button" class="btn btn-green btn-sm" onclick="openSaModal()"><i class="ti ti-plus"></i> Connect account</button>
@endsection

@section('content')
<div class="sa-page">
    <div class="sa-notice">
        <i class="ti ti-shield-check"></i>
        <span>CMO AI connects via official OAuth only. We publish content on your behalf but never read private messages, contacts, or personal data from any account.</span>
    </div>

    <div class="sa-summary-bar">
        <div class="sa-sum-card">
            <div class="sa-sum-icon" style="background:var(--success-lt)"><i class="ti ti-check" style="color:var(--success)"></i></div>
            <div><div class="sa-sum-num">{{ $stats['connected'] }}</div><div class="sa-sum-lbl">Connected accounts</div></div>
        </div>
        <div class="sa-sum-card">
            <div class="sa-sum-icon" style="background:var(--danger-lt)"><i class="ti ti-alert-circle" style="color:var(--danger)"></i></div>
            <div><div class="sa-sum-num">{{ $stats['needs_reconnection'] }}</div><div class="sa-sum-lbl">Needs reconnection</div></div>
        </div>
        <div class="sa-sum-card">
            <div class="sa-sum-icon" style="background:var(--purple-lt)"><i class="ti ti-send" style="color:var(--purple2)"></i></div>
            <div><div class="sa-sum-num">{{ $stats['posts_published'] }}</div><div class="sa-sum-lbl">Posts published</div></div>
        </div>
        <div class="sa-sum-card">
            <div class="sa-sum-icon" style="background:rgba(13,201,160,.08)"><i class="ti ti-eye" style="color:var(--green)"></i></div>
            <div><div class="sa-sum-num">{{ $stats['total_reach'] }}</div><div class="sa-sum-lbl">Total reach</div></div>
        </div>
    </div>

    @foreach($primaryPlatforms as $platformKey)
        @php
            $meta = $platforms[$platformKey];
            $platformAccounts = $grouped->get($platformKey, collect());
            $connectedCount = $platformAccounts->count();
            $expiredCount = $platformAccounts->filter(fn ($a) => $socialAccounts->displayStatus($a) === 'expired')->count();
            $headerNote = $connectedCount === 0
                ? 'Not connected'
                : ($expiredCount > 0
                    ? $connectedCount.' connected · '.$expiredCount.' needs attention'
                    : $connectedCount.' account'.($connectedCount === 1 ? '' : 's').' connected');
        @endphp
        <div class="sa-plat-section">
            <div class="sa-plat-hd">
                <i class="ti {{ $meta['icon'] }}" style="color:{{ $meta['color'] }}"></i>
                <h2>{{ $meta['label'] }}</h2>
                <span>{{ $headerNote }}</span>
            </div>
            <div class="sa-acct-grid">
                @foreach($platformAccounts as $account)
                    @include('app.brand.partials.social-account-card', [
                        'account' => $account,
                        'socialAccounts' => $socialAccounts,
                        'platforms' => $platforms,
                    ])
                @endforeach
                <form method="POST" action="{{ route('app.brand.social-accounts.connect') }}" class="sa-add-card-form">
                    @csrf
                    <input type="hidden" name="platform" value="{{ $platformKey }}">
                    <button type="submit" class="sa-add-card">
                        <i class="ti ti-plus"></i>
                        <h4>{{ $meta['add_title'] ?? 'Connect '.$meta['label'] }}</h4>
                        <p>{{ $meta['add_sub'] ?? 'Connect via official OAuth' }}</p>
                    </button>
                </form>
            </div>
        </div>
    @endforeach

    {{-- Available platforms (YouTube, Pinterest, Threads, Google Business) hidden for now
    <div class="sa-plat-section">
        <div class="sa-plat-hd">
            <h2 style="color:var(--text3)">Available platforms</h2>
        </div>
        <div class="sa-available-grid">
            @foreach($availablePlatforms as $platformKey)
                @php
                    $meta = $platforms[$platformKey];
                    $platformAccounts = $grouped->get($platformKey, collect());
                @endphp
                @foreach($platformAccounts as $account)
                    <div class="sa-available-connected">
                        @include('app.brand.partials.social-account-card', [
                            'account' => $account,
                            'socialAccounts' => $socialAccounts,
                            'platforms' => $platforms,
                        ])
                    </div>
                @endforeach
                @if($platformAccounts->isEmpty())
                    <form method="POST" action="{{ route('app.brand.social-accounts.connect') }}" class="sa-add-inline-form">
                        @csrf
                        <input type="hidden" name="platform" value="{{ $platformKey }}">
                        <button type="submit" class="sa-add-card-inline">
                            <i class="ti {{ $meta['icon'] }} sa-pi" style="color:{{ $meta['color'] }}"></i>
                            <div>
                                <h4>{{ $meta['label'] }}</h4>
                                <p>{{ $meta['add_sub'] ?? 'Connect this platform' }}</p>
                            </div>
                            <i class="ti ti-arrow-right sa-arr"></i>
                        </button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>
    --}}
</div>

<div class="sa-modal-ov" id="sa-modal" onclick="if(event.target===this)closeSaModal()">
    <div class="sa-modal">
        <div class="sa-modal-top">
            <div>
                <h2>Connect a social account</h2>
                <p>Choose a platform to connect via official OAuth</p>
            </div>
            <button type="button" class="sa-close-btn" onclick="closeSaModal()"><i class="ti ti-x"></i></button>
        </div>
        @foreach($primaryPlatforms as $platformKey)
            @php $meta = $platforms[$platformKey]; @endphp
            <form method="POST" action="{{ route('app.brand.social-accounts.connect') }}" class="sa-plat-opt-form">
                @csrf
                <input type="hidden" name="platform" value="{{ $platformKey }}">
                <button type="submit" class="sa-plat-opt">
                    <i class="ti {{ $meta['icon'] }} sa-pi" style="color:{{ $meta['color'] }}"></i>
                    <div>
                        <div class="sa-po-name">{{ $meta['label'] }}</div>
                        <div class="sa-po-sub">{{ $meta['add_sub'] ?? 'Official OAuth connection' }}</div>
                    </div>
                    <i class="ti ti-arrow-right sa-arr"></i>
                </button>
            </form>
        @endforeach
        {{-- @foreach($availablePlatforms as $platformKey) ... YouTube, Pinterest, Threads, Google Business hidden --}}
        <div class="sa-modal-pad-bot"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openSaModal() {
    document.getElementById('sa-modal')?.classList.add('open');
}
function closeSaModal() {
    document.getElementById('sa-modal')?.classList.remove('open');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSaModal();
});
</script>
@endpush
