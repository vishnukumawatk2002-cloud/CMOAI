<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\SocialConnectService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialConnectController extends Controller
{
    private const SUPPORTED = ['facebook', 'x', 'linkedin', 'youtube', 'snapchat'];

    private const ALLOWED_RETURNS = ['onboarding.wizard', 'app.brand.social-accounts'];

    /** @var array<string, array{driver: string, config_key: string, label: string}> */
    private const PLATFORM_META = [
        'facebook' => ['driver' => 'facebook', 'config_key' => 'facebook', 'label' => 'Facebook'],
        'x' => ['driver' => 'x', 'config_key' => 'x', 'label' => 'X'],
        'linkedin' => ['driver' => 'linkedin-openid', 'config_key' => 'linkedin-openid', 'label' => 'LinkedIn'],
        'youtube' => ['driver' => 'youtube', 'config_key' => 'youtube', 'label' => 'YouTube'],
        'snapchat' => ['driver' => 'snapchat', 'config_key' => 'snapchat', 'label' => 'Snapchat'],
    ];

    public function __construct(private readonly SocialConnectService $connect)
    {
    }

    public function redirect(Request $request, string $platform): RedirectResponse
    {
        $brand = $this->resolveBrand($request);

        if (! $brand) {
            return redirect()->route('onboarding.brand.create');
        }

        $returnRoute = $this->resolveReturnRoute($request);

        session([
            'current_brand_id' => $brand->id,
            'social_connect_brand_id' => $brand->id,
            'social_connect_return' => $returnRoute,
        ]);

        // Instagram connects via Facebook Page (same as Social Accounts), not Socialite.
        if ($platform === 'instagram') {
            if (! filled(config('services.facebook.client_id'))) {
                return $this->returnAfterConnect($request, $returnRoute)
                    ->with('error', 'Instagram connect is not configured. Add FACEBOOK_CLIENT_ID and FACEBOOK_CLIENT_SECRET to your .env file.');
            }

            $facebookPages = $this->connect->facebookPagesForInstagramPicker($brand);

            if ($facebookPages === []) {
                return $this->returnAfterConnect($request, $returnRoute)
                    ->with('error', 'Pehle Facebook Page connect karo, phir Instagram connect karo.');
            }

            session(['instagram_connect_return' => $returnRoute]);

            return redirect()->route('app.brand.social-accounts.instagram-pages');
        }

        if (! in_array($platform, self::SUPPORTED, true)) {
            return $this->returnAfterConnect($request, $returnRoute)
                ->with('error', 'This platform is not supported yet.');
        }

        $meta = self::PLATFORM_META[$platform];

        if (! config("services.{$meta['config_key']}.client_id")) {
            return $this->returnAfterConnect($request, $returnRoute)
                ->with('error', "{$meta['label']} connect is not configured. Add {$this->envHint($platform)} to your .env file.");
        }

        session([
            'social_connect_platform' => $platform,
        ]);

        $driver = Socialite::driver($meta['driver']);

        if ($platform === 'facebook') {
            $params = [
                'auth_type' => 'rerequest',
                'enable_profile_selector' => 'true',
            ];

            if (filled(config('services.facebook.login_config_id'))) {
                $params['config_id'] = (string) config('services.facebook.login_config_id');
                $params['override_default_response_type'] = 'true';
            }

            $driver->scopes(config('services.facebook.scopes'))->with($params);
        }

        if ($platform === 'x') {
            $driver->setScopes(config('services.x.scopes', [
                'tweet.read',
                'tweet.write',
                'users.read',
                'offline.access',
                'media.write',
            ]));
        }

        if ($platform === 'linkedin') {
            $driver->scopes(config('services.linkedin-openid.scopes', ['openid', 'profile', 'email', 'w_member_social']));
        }

        if ($platform === 'youtube') {
            $driver->scopes(config('services.youtube.scopes'))->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);
        }

        if ($platform === 'snapchat') {
            $driver->scopes(config('services.snapchat.scopes'));
        }

        return $driver->redirect();
    }

   

    public function callback(Request $request, string $platform): RedirectResponse
    {
        $returnRoute = session('social_connect_return', $this->resolveReturnRoute($request));
        $meta = self::PLATFORM_META[$platform] ?? null;

        if ($platform !== session('social_connect_platform') || ! in_array($platform, self::SUPPORTED, true) || $meta === null) {
            return $this->returnAfterConnect($request, $returnRoute)
                ->with('error', 'Social connect session expired. Please try again.');
        }

        if ($request->filled('error')) {
            $this->clearConnectSession();
            $description = (string) ($request->query('error_description') ?: $request->query('error'));

            return $this->returnAfterConnect($request, $returnRoute)
                ->with('error', "{$meta['label']} authorization was denied or failed: {$description}");
        }

        if (! $request->filled('code')) {
            $this->clearConnectSession();

            return $this->returnAfterConnect($request, $returnRoute)
                ->with('error', "{$meta['label']} did not return an authorization code. Check that the Callback URI in your X Developer app exactly matches: ".config('services.x.redirect'));
        }

        $brand = Brand::query()
            ->where('id', session('social_connect_brand_id'))
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $brand) {
            $this->clearConnectSession();

            return $this->returnAfterConnect($request, $returnRoute)->with('error', 'Brand not found.');
        }

        try {
            $driver = Socialite::driver($meta['driver']);

            if ($platform === 'facebook') {
                $driver = $driver->fields(['id', 'name', 'email']);
            }

            $socialUser = $driver->user();

            if ($platform === 'facebook') {
                $oauthPayload = $this->connect->fetchFacebookPagesForConnect($socialUser);
                $payload = $this->connect->buildFacebookPickerPayload(
                    $brand,
                    $oauthPayload['user_token'],
                    $oauthPayload['expires_in']
                );

                session([
                    'facebook_connect_brand_id' => $brand->id,
                    'facebook_connect_user_token' => $payload['user_token'],
                    'facebook_connect_expires_in' => $payload['expires_in'],
                    'facebook_connect_pages' => $payload['pages'],
                    'facebook_connect_return' => $returnRoute,
                ]);

                $this->clearConnectSession();

                return redirect()->route('app.brand.social-accounts.facebook-pages');
            }

            $account = match ($platform) {
                'x' => $this->connect->connectX($brand, $socialUser),
                'linkedin' => $this->connect->connectLinkedIn($brand, $socialUser),
                'youtube' => $this->connect->connectYouTube($brand, $socialUser),
                'snapchat' => $this->connect->connectSnapchat($brand, $socialUser),
                default => throw new \RuntimeException('Unsupported platform.'),
            };
        } catch (Throwable $e) {
            $this->clearConnectSession();

            return $this->returnAfterConnect($request, $returnRoute)
                ->with('error', "{$meta['label']} connection failed: ".$this->friendlyConnectError($e));
        }

        $this->clearConnectSession();

        return $this->returnAfterConnect($request, $returnRoute)
            ->with('success', "{$meta['label']} connected: {$account->account_name}");
    }

    private function envHint(string $platform): string
    {
        return match ($platform) {
            'x' => 'X_CLIENT_ID and X_CLIENT_SECRET',
            'linkedin' => 'LINKEDIN_CLIENT_ID and LINKEDIN_CLIENT_SECRET',
            'youtube' => 'YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET (or reuse GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET)',
            'snapchat' => 'SNAPCHAT_CLIENT_ID and SNAPCHAT_CLIENT_SECRET from Snapchat Ads Manager OAuth app',
            default => 'FACEBOOK_CLIENT_ID and FACEBOOK_CLIENT_SECRET',
        };
    }

    private function friendlyConnectError(Throwable $e): string
    {
        $message = $e->getMessage();

        if (
            str_contains($message, 'Application request limit reached')
            || str_contains($message, '(#4)')
            || str_contains($message, '"code":4')
            || str_contains($message, 'code":4')
        ) {
            return 'Facebook API limit hit ho gaya hai (bahut requests). 30–60 minute Facebook/Instagram connect mat try karo — dobara try karne se limit aur late reset hoti hai.';
        }

        if (str_contains($message, '(#17)') || str_contains($message, 'User request limit reached')) {
            return 'Facebook user API limit hit. 15–30 minute wait karke phir try karo.';
        }

        // Never flash raw Graph URLs (they can contain access tokens).
        $message = preg_replace('/access_token=[^&\s]+/i', 'access_token=***', $message) ?? $message;
        $message = preg_replace('/appsecret_proof=[^&\s]+/i', 'appsecret_proof=***', $message) ?? $message;

        if (strlen($message) > 280) {
            $message = substr($message, 0, 280).'…';
        }

        return $message;
    }

    private function resolveBrand(Request $request): ?Brand
    {
        $brandId = session('current_brand_id');
        $query = $request->user()->brands();

        if ($brandId) {
            return $query->where('id', $brandId)->first();
        }

        return $query->latest()->first();
    }

    private function returnAfterConnect(Request $request, ?string $returnRoute = null): RedirectResponse
    {
        $route = $returnRoute ?? $this->resolveReturnRoute($request);

        if ($route === 'app.brand.social-accounts') {
            return redirect()->route('app.brand.social-accounts');
        }

        return redirect()
            ->route('onboarding.wizard')
            ->with('step', 5);
    }

    private function resolveReturnRoute(Request $request): string
    {
        $route = session('social_connect_return', $request->query('return', 'app.brand.social-accounts'));

        if (! in_array($route, self::ALLOWED_RETURNS, true)) {
            return 'app.brand.social-accounts';
        }

        return $route;
    }

    private function clearConnectSession(): void
    {
        session()->forget(['social_connect_brand_id', 'social_connect_platform', 'social_connect_return']);
    }
}
