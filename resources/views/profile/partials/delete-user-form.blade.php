<section>
    <h2 class="h5 fw-semibold text-danger mb-1">{{ __('Delete account') }}</h2>
    <p class="text-muted small mb-4">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</p>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
        {{ __('Delete account') }}
    </button>

    <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Are you sure?') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">{{ __('Please enter your password to confirm you would like to permanently delete your account.') }}</p>
                        <x-input-label for="delete_password" value="Password" class="sr-only" />
                        <x-text-input id="delete_password" name="password" type="password" placeholder="{{ __('Password') }}" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <x-danger-button>{{ __('Delete account') }}</x-danger-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
