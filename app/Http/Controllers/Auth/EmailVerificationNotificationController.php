<?php

namespace App\Http\Controllers\Auth;

use App\Application\Services\Auth\EmailVerificationService;
use App\Http\Controllers\Auth\Concerns\RedirectsAfterAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    use RedirectsAfterAuth;

    public function __construct(private readonly EmailVerificationService $verification)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($request->user());
        }

        $this->verification->sendOtp($request->user());

        return back()->with('status', 'A new verification code has been sent.');
    }
}
