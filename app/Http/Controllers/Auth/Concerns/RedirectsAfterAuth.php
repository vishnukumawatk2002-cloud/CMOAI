<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

trait RedirectsAfterAuth
{
    protected function redirectAfterAuth(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $user->brands()->exists()) {
            return redirect()
                ->route('app.dashboard')
                ->with('show_brand_modal', true);
        }

        return redirect()->route('app.dashboard');
    }
}
