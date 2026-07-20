<?php

namespace App\Http\Controllers\Auth;

use App\Application\Services\Auth\EmailVerificationService;
use App\Http\Controllers\Auth\Concerns\RedirectsAfterAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Auth\VerifyOtpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OtpVerificationController extends Controller
{
    use RedirectsAfterAuth;

    public function __construct(private readonly EmailVerificationService $verification)
    {
    }

    public function store(VerifyOtpRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user);
        }

        if (! $this->verification->verify($user, $request->validated('otp'))) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        return $this->redirectAfterAuth($user)
            ->with('success', 'Email verified successfully!');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user);
        }

        $this->verification->sendOtp($user);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }
}
