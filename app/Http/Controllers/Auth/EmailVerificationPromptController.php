<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsAfterAuth;
use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    use RedirectsAfterAuth;

    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user);
        }

        if (EmailVerification::query()
            ->where('user_id', $user->id)
            ->whereNotNull('verified_at')
            ->exists()) {
            $user->markEmailAsVerified();

            return $this->redirectAfterAuth($user)
                ->with('success', 'Email verified successfully!');
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
        ]);
    }
}
