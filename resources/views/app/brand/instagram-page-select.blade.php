@php
    $backUrl = ($returnRoute ?? '') === 'onboarding.wizard'
        ? route('onboarding.wizard')
        : route('app.brand.social-accounts');
@endphp

@extends('layouts.app')

@section('title', 'Connect Instagram — '.$brand->name)
@section('pageTitle', 'Connect Instagram')

@section('topbarExtra')
    <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Back</a>
@endsection

@section('content')
<div class="sa-page">
    @if (session('error'))
        <div class="sa-notice" style="margin-bottom:12px;background:#fef2f2;border-color:#fecaca;color:#991b1b;">
            <i class="ti ti-alert-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (count($instagramAccounts) > 0)
        <div class="sa-notice">
            <i class="ti ti-brand-instagram" style="color:#E1306C"></i>
            <span>
                Yeh Instagram accounts Facebook Pages se mil gaye.
                <strong>{{ $brand->name }}</strong> pe connect karne ke liye Instagram select karo — Facebook Page alag se select karne ki zarurat nahi.
            </span>
        </div>

        <form method="POST" action="{{ route('app.brand.social-accounts.instagram-pages.store') }}" class="fb-page-select">
            @csrf

            <div class="fb-page-grid">
                @php $firstSelectable = collect($instagramAccounts)->first(fn ($a) => empty($a['already_connected'])); @endphp
                @foreach ($instagramAccounts as $ig)
                    @php
                        $accountId = (int) ($ig['facebook_account_id'] ?? 0);
                        $picture = $ig['picture_url'] ?? null;
                        $fans = (int) ($ig['followers_count'] ?? 0);
                        $already = ! empty($ig['already_connected']);
                        $isDefault = $firstSelectable && (int) ($firstSelectable['facebook_account_id'] ?? 0) === $accountId
                            && ($firstSelectable['instagram_id'] ?? '') === ($ig['instagram_id'] ?? '');
                    @endphp
                    <label class="fb-page-card {{ $already ? 'is-connected' : '' }}">
                        <input
                            type="radio"
                            name="facebook_account_id"
                            value="{{ $accountId }}"
                            @checked($isDefault)
                            @disabled($already)
                            required
                        >
                        <div class="fb-page-card-inner">
                            <div class="fb-page-av" @if($picture) style="background-image:url('{{ $picture }}')" @endif>
                                @unless($picture)
                                    <i class="ti ti-brand-instagram"></i>
                                @endunless
                            </div>
                            <div class="fb-page-info">
                                <div class="fb-page-name">{{ $ig['name'] ?? 'Instagram' }}</div>
                                <div class="fb-page-sub">
                                    @if (! empty($ig['username']))
                                        {{ '@'.$ig['username'] }} ·
                                    @endif
                                    {{ number_format($fans) }} followers
                                    · via {{ $ig['facebook_page_name'] ?? 'Facebook Page' }}
                                </div>
                            </div>
                            @if ($already)
                                <span class="fb-page-badge">Connected</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="fb-page-actions">
                <a href="{{ $backUrl }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-green"><i class="ti ti-brand-instagram"></i> Connect Instagram</button>
            </div>
        </form>
    @else
        <div class="sa-notice">
            <i class="ti ti-brand-instagram" style="color:#E1306C"></i>
            <span>
                Abhi kisi connected Facebook Page se Instagram Business account nahi mil raha.
                Pehle jis Page pe Instagram linked hai, uska <strong>Facebook Connect / Reconnect</strong> karo —
                dialog me woh Page + Instagram allow karo.
            </span>
        </div>

        <div class="sa-notice" style="margin-top:10px;background:#fff8e6;border-color:#f0d78c;">
            <i class="ti ti-alert-circle" style="color:#b8860b"></i>
            <span>
                Page pe Instagram linked hone ke baad yahan dubara Connect Instagram try karo.
                <a href="{{ $backUrl }}" style="margin-left:6px;font-weight:600;">Back →</a>
            </span>
        </div>

        <form method="POST" action="{{ route('app.brand.social-accounts.instagram-pages.store') }}" class="fb-page-select" style="margin-top:16px">
            @csrf
            <div class="fb-page-grid">
                @php $firstSelectable = collect($pages)->first(fn ($p) => ! empty($p['has_token'])); @endphp
                @foreach ($pages as $page)
                    @php
                        $accountId = (int) ($page['social_account_id'] ?? 0);
                        $picture = $page['picture_url'] ?? null;
                        $fans = (int) ($page['fan_count'] ?? 0);
                        $disabled = empty($page['has_token']);
                        $isDefault = $firstSelectable && (int) ($firstSelectable['social_account_id'] ?? 0) === $accountId;
                    @endphp
                    <label class="fb-page-card {{ $disabled ? 'is-connected' : '' }}">
                        <input
                            type="radio"
                            name="facebook_account_id"
                            value="{{ $accountId }}"
                            @checked($isDefault)
                            @disabled($disabled)
                            required
                        >
                        <div class="fb-page-card-inner">
                            <div class="fb-page-av" @if($picture) style="background-image:url('{{ $picture }}')" @endif>
                                @unless($picture)
                                    <i class="ti ti-brand-facebook"></i>
                                @endunless
                            </div>
                            <div class="fb-page-info">
                                <div class="fb-page-name">{{ $page['name'] ?? 'Facebook Page' }}</div>
                                <div class="fb-page-sub">
                                    {{ number_format($fans) }} followers
                                    · token refresh needed for Instagram
                                </div>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="fb-page-actions">
                <a href="{{ $backUrl }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-green"><i class="ti ti-brand-instagram"></i> Try Connect Instagram</button>
            </div>
        </form>
    @endif
</div>
@endsection
