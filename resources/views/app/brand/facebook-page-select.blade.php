@extends('layouts.app')

@section('title', 'Select Facebook pages — '.$brand->name)
@section('pageTitle', 'Select Facebook pages')

@section('topbarExtra')
    <a href="{{ route('app.brand.social-accounts') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Back</a>
@endsection

@section('content')
<div class="sa-page">
    <div class="sa-notice">
        <i class="ti ti-brand-facebook" style="color:#1877F2"></i>
        <span>Select one or more Facebook Pages to connect to <strong>{{ $brand->name }}</strong>. The same Page can be used on multiple brands. Pages already linked here are marked.</span>
    </div>

    <div class="sa-notice" style="margin-top:10px;background:#fff8e6;border-color:#f0d78c;">
        <i class="ti ti-alert-circle" style="color:#b8860b"></i>
        <span>
            Jo page list me nahi hai (pehle jo chal raha tha), Facebook ko dubara Pages select karne do —
            dialog me <strong>saari Pages tick</strong> karke Continue dabao.
            <a href="{{ route('onboarding.social.connect', ['platform' => 'facebook', 'return' => 'app.brand.social-accounts']) }}" style="margin-left:6px;font-weight:600;">
                Facebook se pages dubara mangao →
            </a>
        </span>
    </div>

    <form method="POST" action="{{ route('app.brand.social-accounts.facebook-pages.store') }}" class="fb-page-select">
        @csrf

        <div class="fb-page-grid">
            @foreach ($pages as $page)
                @php
                    $pageId = (string) ($page['id'] ?? '');
                    $already = in_array($pageId, $connectedIds, true);
                    $picture = $page['picture_url'] ?? null;
                    $fans = (int) ($page['fan_count'] ?? 0);
                @endphp
                <label class="fb-page-card {{ $already ? 'is-connected' : '' }}">
                    <input type="checkbox" name="page_ids[]" value="{{ $pageId }}" @checked(! $already)>
                    <div class="fb-page-card-inner">
                        <div class="fb-page-av" @if($picture) style="background-image:url('{{ $picture }}')" @endif>
                            @unless($picture)
                                <i class="ti ti-brand-facebook"></i>
                            @endunless
                        </div>
                        <div class="fb-page-info">
                            <div class="fb-page-name">{{ $page['name'] ?? 'Facebook Page' }}</div>
                            <div class="fb-page-sub">
                                @if (! empty($page['username']))
                                    {{ '@'.$page['username'] }} ·
                                @endif
                                {{ number_format($fans) }} followers
                                @if (! empty($page['was_disconnected']))
                                    · previously disconnected
                                @endif
                            </div>
                        </div>
                        @if ($already)
                            <span class="fb-page-badge">Connected</span>
                        @elseif (! empty($page['was_disconnected']))
                            <span class="fb-page-badge" style="background:#fff3cd;color:#856404;">Reconnect</span>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>

        @if ($pages === [])
            <p class="sched-empty">No Facebook Pages were returned. Reconnect and allow Page access.</p>
        @endif

        <div class="fb-page-actions">
            <a href="{{ route('app.brand.social-accounts') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-green"><i class="ti ti-check"></i> Connect selected pages</button>
        </div>
    </form>
</div>
@endsection
