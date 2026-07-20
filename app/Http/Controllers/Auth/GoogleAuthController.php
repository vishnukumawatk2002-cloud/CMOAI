<?php

namespace App\Http\Controllers\Auth;

use App\Application\Services\Auth\AuthService;
use App\Http\Controllers\Auth\Concerns\RedirectsAfterAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    use RedirectsAfterAuth;

    public function __construct(private readonly AuthService $auth)
    {
    }

    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')
                ->with('error', 'Google login is not configured. Add GOOGLE_CLIENT_ID to your .env file.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('error', 'Google sign-in was cancelled or failed.');
        }

        $user = $this->auth->findOrCreateFromGoogle($googleUser);

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return $this->redirectAfterAuth($user);
    }
}
