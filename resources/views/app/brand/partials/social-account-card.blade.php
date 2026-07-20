@props(['account', 'socialAccounts', 'platforms'])

@php
    $platform = $account->platform;
    $meta = $platforms[$platform] ?? [];
    $status = $socialAccounts->displayStatus($account);
    $isExpired = $status === 'expired';
    $postsCount = $socialAccounts->postsPublishedCount($account);
    $initials = $socialAccounts->avatarInitials($account);
    $avatarStyle = $socialAccounts->avatarStyle($platform);
    $profileImage = filled($account->profile_image_url) ? $account->profile_image_url : null;
    $tokenExpiredAt = $account->oauthToken?->expires_at;
    $reachDisplay = $account->follower_count >= 1000
        ? round($account->follower_count / 1000, 1).'K'
        : (string) $account->follower_count;
@endphp

<div class="sa-acct-card {{ $isExpired ? 'err' : 'ok' }}">
    <div class="sa-acct-head">
        @if ($profileImage)
            <div class="sa-acct-av sa-acct-av-img" style="background-image:url('{{ $profileImage }}')" title="{{ $account->account_name }}" role="img" aria-label="{{ $account->account_name }}"></div>
        @else
            <div class="sa-acct-av" style="{{ $avatarStyle }}">{{ $initials }}</div>
        @endif
        <div class="sa-acct-info">
            <div class="sa-acct-name">{{ $account->account_name }}</div>
            <div class="sa-acct-sub">{{ $socialAccounts->accountSubtitle($account) }}</div>
        </div>
        @if($isExpired)
            <span class="badge sa-badge-err"><span class="sa-dot sa-dot-err"></span>Expired</span>
        @else
            <span class="badge sa-badge-ok"><span class="sa-dot sa-dot-ok"></span>Active</span>
        @endif
    </div>
    <div class="sa-acct-body">
        @if($isExpired)
            <div class="sa-err-box">
                <i class="ti ti-alert-circle"></i>
                @if($tokenExpiredAt)
                    Token expired on {{ $tokenExpiredAt->format('M j, Y') }}. Reconnect to resume publishing.
                @else
                    Connection expired. Reconnect to resume publishing.
                @endif
            </div>
            <div class="sa-acct-acts">
                <form method="POST" action="{{ route('app.brand.social-accounts.connect') }}">
                    @csrf
                    <input type="hidden" name="platform" value="{{ $platform }}">
                    <button type="submit" class="sa-acct-btn warn"><i class="ti ti-refresh"></i> Reconnect</button>
                </form>
                <form method="POST" action="{{ route('app.brand.social-accounts.destroy', $account) }}" onsubmit="return confirm('Remove this account?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="sa-acct-btn danger"><i class="ti ti-unlink"></i> Remove</button>
                </form>
            </div>
        @else
            <div class="sa-acct-stats">
                <div>
                    <div class="sa-as-n">{{ $postsCount }}</div>
                    <div class="sa-as-l">Posts published</div>
                </div>
                <div>
                    <div class="sa-as-n">{{ $reachDisplay }}</div>
                    <div class="sa-as-l">Reach</div>
                </div>
                <div>
                    <div class="sa-as-n">—</div>
                    <div class="sa-as-l">Engagement</div>
                </div>
            </div>
            <div class="sa-acct-acts">
                <a href="{{ route('app.analytics') }}" class="sa-acct-btn"><i class="ti ti-chart-bar"></i> Analytics</a>
                @if(in_array($platform, ['facebook', 'instagram', 'x', 'linkedin', 'youtube', 'snapchat'], true) && ($meta['oauth'] ?? false))
                    <form method="POST" action="{{ route('app.brand.social-accounts.connect') }}">
                        @csrf
                        <input type="hidden" name="platform" value="{{ $platform }}">
                        <button type="submit" class="sa-acct-btn warn"><i class="ti ti-refresh"></i> Reconnect</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('app.brand.social-accounts.destroy', $account) }}" onsubmit="return confirm('Disconnect this account?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="sa-acct-btn danger"><i class="ti ti-unlink"></i> Disconnect</button>
                </form>
            </div>
        @endif
    </div>
</div>
