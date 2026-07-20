<?php

namespace App\Application\Services\Auth;

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public function sendOtp(User $user): EmailVerification
    {
        EmailVerification::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        $otp = (string) random_int(100000, 999999);

        $verification = EmailVerification::query()->create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Throwable $e) {
            logger()->error("Failed to send OTP email to {$user->email}: {$e->getMessage()}");
            session()->flash('mail_error', 'Email could not be sent: '.$e->getMessage());
        }

        if (in_array(config('mail.default'), ['log', 'array'], true) || config('app.debug')) {
            session()->flash('dev_otp', $otp);
        }

        logger()->info("OTP for {$user->email}: {$otp}");

        return $verification;
    }

    public function verify(User $user, string $otp): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $verification = EmailVerification::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $verification || ! Hash::check($otp, $verification->otp_hash)) {
            $verification?->increment('attempts');

            return false;
        }

        $verification->update(['verified_at' => now()]);
        $user->markEmailAsVerified();

        return true;
    }
}
