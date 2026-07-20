@extends('layouts.guest')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 fw-bold mb-2">{{ __('Confirm password') }}</h1>
        <p class="text-muted mb-4">{{ __('This is a secure area. Please confirm your password before continuing.') }}</p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <x-primary-button class="w-100 py-2">{{ __('Confirm') }}</x-primary-button>
        </form>
    </div>
</div>
@endsection
