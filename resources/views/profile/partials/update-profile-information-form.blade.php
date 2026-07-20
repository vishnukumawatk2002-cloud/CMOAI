<section>
    <h2 class="h5 fw-semibold mb-1">{{ __('Profile information') }}</h2>
    <p class="text-muted small mb-4">{{ __("Update your account's profile information and email address.") }}</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-1" :messages="$errors->get('first_name')" />
            </div>
            <div class="col-md-6">
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-1" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="small text-muted mb-1">{{ __('Your email address is unverified.') }}</p>
                    <button form="send-verification" class="btn btn-link btn-sm p-0">{{ __('Click here to re-send the verification email.') }}</button>
                    @if (session('status') === 'verification-link-sent')
                        <p class="small text-success mt-1">{{ __('A new verification link has been sent.') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <span class="text-success small">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>
