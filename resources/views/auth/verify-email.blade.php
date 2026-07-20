@extends('layouts.guest')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4 p-md-5 text-center">
        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="text-success" viewBox="0 0 16 16">
                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zM13.997 4.697v7.104l-5.803-3.558z"/>
            </svg>
        </div>

        <h1 class="h4 fw-bold mb-2">{{ __('Verify your email') }}</h1>
        <p class="text-muted mb-4">
            {{ __('Thanks for signing up! Before getting started, please verify your email address by clicking the link we sent you.') }}
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success">{{ __('A new verification link has been sent to your email.') }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
            @csrf
            <x-primary-button>{{ __('Resend verification email') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-link text-muted">{{ __('Log out') }}</button>
        </form>
    </div>
</div>
@endsection
