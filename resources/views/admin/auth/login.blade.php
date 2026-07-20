@extends('layouts.guest')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 fw-bold mb-1">Admin Login</h1>
        <p class="text-muted mb-4">Sign in to the CMO AI admin panel</p>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <div class="mb-3">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" />
                <div class="pw-wrap">
                    <x-text-input id="password" type="password" name="password" required />
                    <button type="button" class="eye-btn" id="admin-pw-toggle" aria-label="Show password">
                        <i class="ti ti-eye" id="admin-pw-icon"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <x-primary-button class="w-100 py-2">Log in</x-primary-button>
        </form>
    </div>
</div>

<script>
document.getElementById('admin-pw-toggle')?.addEventListener('click', () => {
    const input = document.getElementById('password');
    const icon = document.getElementById('admin-pw-icon');
    if (!input || !icon) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'ti ti-eye-off' : 'ti ti-eye';
});
</script>
@endsection
