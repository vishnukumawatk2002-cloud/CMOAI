<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\SocialAccountService;
use App\Application\Services\Brand\SocialConnectService;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialAccountController extends Controller
{
    public function __construct(
        private readonly SocialAccountService $socialAccounts,
        private readonly SocialConnectService $socialConnect,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $this->socialConnect->refreshFacebookAccountsForBrand($brand);
        $accounts = $brand->socialAccounts()->with('oauthToken')->orderBy('platform')->orderBy('account_name')->get();
        $grouped = $this->socialAccounts->groupByPlatform($accounts);

        return view('app.brand.social-accounts', [
            'brand' => $brand,
            'accounts' => $accounts,
            'grouped' => $grouped,
            'stats' => $this->socialAccounts->summaryStats($brand, $accounts),
            'platforms' => $this->socialAccounts->platformDefinitions(),
            'primaryPlatforms' => $this->socialAccounts->primaryPlatformKeys(),
            'availablePlatforms' => $this->socialAccounts->availablePlatformKeys(),
            'socialAccounts' => $this->socialAccounts,
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:'.implode(',', array_keys($this->socialAccounts->platformDefinitions()))],
        ]);

        $platform = $validated['platform'];

        if ($platform === 'instagram' && $this->socialAccounts->isOAuthConfigured('instagram')) {
            $facebookPages = $this->socialConnect->facebookPagesForInstagramPicker($brand);

            if ($facebookPages === []) {
                return redirect()
                    ->route('app.brand.social-accounts')
                    ->with('error', 'Pehle Facebook Page connect karo, phir Instagram connect karo.');
            }

            session(['instagram_connect_return' => 'app.brand.social-accounts']);

            return redirect()->route('app.brand.social-accounts.instagram-pages');
        }

        if ($platform === 'facebook' && $this->socialAccounts->supportsOAuth($platform) && $this->socialAccounts->isOAuthConfigured($platform)) {
            // Always open Facebook login again so Meta can ask for Pages (including ones that worked before).
            // Old token reuse only shows pages already granted — missing pages need auth_type=rerequest.
            return redirect()->route('onboarding.social.connect', [
                'platform' => $platform,
                'return' => 'app.brand.social-accounts',
            ]);
        }

        if (in_array($platform, ['x', 'linkedin', 'youtube', 'snapchat'], true) && $this->socialAccounts->supportsOAuth($platform)) {
            if (! $this->socialAccounts->isOAuthConfigured($platform)) {
                $envHint = match ($platform) {
                    'x' => 'Add X_CLIENT_ID and X_CLIENT_SECRET to your .env file.',
                    'linkedin' => 'Add LINKEDIN_CLIENT_ID and LINKEDIN_CLIENT_SECRET to your .env file.',
                    'youtube' => 'Add YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET (or reuse GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET) and enable YouTube Data API v3.',
                    'snapchat' => 'Add SNAPCHAT_CLIENT_ID and SNAPCHAT_CLIENT_SECRET from Snapchat Ads Manager OAuth app.',
                    default => 'OAuth is not configured for this platform.',
                };

                return redirect()
                    ->route('app.brand.social-accounts')
                    ->with('error', ($platform === 'x' ? 'X' : $this->socialAccounts->platformDefinitions()[$platform]['label']).' connect is not configured. '.$envHint);
            }

            return redirect()->route('onboarding.social.connect', [
                'platform' => $platform,
                'return' => 'app.brand.social-accounts',
            ]);
        }

        try {
            $account = $this->socialAccounts->connectDemo($brand, $platform);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = $this->socialAccounts->platformDefinitions()[$platform]['label'] ?? ucfirst($platform);

        return redirect()
            ->route('app.brand.social-accounts')
            ->with('success', $label.' connected: '.$account->account_name);
    }

    public function selectFacebookPages(Request $request): View|RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');
        $sessionBrandId = (int) session('facebook_connect_brand_id');
        $pages = session('facebook_connect_pages', []);
        $userToken = session('facebook_connect_user_token');

        if ($sessionBrandId !== (int) $brand->id || ! is_array($pages) || $pages === [] || ! filled($userToken)) {
            return redirect()
                ->route('app.brand.social-accounts')
                ->with('error', 'Facebook page selection expired. Click Add another Facebook page and connect again.');
        }

        $connectedIds = $brand->socialAccounts()
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->pluck('external_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return view('app.brand.facebook-page-select', [
            'brand' => $brand,
            'pages' => $pages,
            'connectedIds' => $connectedIds,
        ]);
    }

    public function storeFacebookPages(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');
        $sessionBrandId = (int) session('facebook_connect_brand_id');
        $userToken = session('facebook_connect_user_token');
        $expiresIn = session('facebook_connect_expires_in');

        if ($sessionBrandId !== (int) $brand->id || ! filled($userToken)) {
            return redirect()
                ->route('app.brand.social-accounts')
                ->with('error', 'Facebook page selection expired. Click Add another Facebook page and connect again.');
        }

        $validated = $request->validate([
            'page_ids' => ['required', 'array', 'min:1'],
            'page_ids.*' => ['required', 'string'],
        ]);

        try {
            $accounts = $this->socialConnect->connectFacebookPages(
                $brand,
                $validated['page_ids'],
                (string) $userToken,
                is_numeric($expiresIn) ? (int) $expiresIn : null,
                is_array(session('facebook_connect_pages')) ? session('facebook_connect_pages') : []
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('app.brand.social-accounts.facebook-pages')
                ->with('error', 'Facebook connection failed: '.$e->getMessage());
        }

        session()->forget([
            'facebook_connect_brand_id',
            'facebook_connect_user_token',
            'facebook_connect_expires_in',
            'facebook_connect_pages',
            'facebook_connect_return',
        ]);

        $names = collect($accounts)->pluck('account_name')->filter()->implode(', ');

        return redirect()
            ->route('app.brand.social-accounts')
            ->with('success', 'Facebook connected: '.$names);
    }

    public function selectInstagramPage(Request $request): View|RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');
        $returnRoute = session('instagram_connect_return', 'app.brand.social-accounts');

        try {
            $instagramAccounts = $this->socialConnect->discoverInstagramAccountsForBrand($brand);
            $facebookPages = $this->socialConnect->facebookPagesForInstagramPicker($brand);
        } catch (\Throwable $e) {
            return $this->redirectAfterInstagram($returnRoute)
                ->with('error', 'Instagram pages load nahi ho payi: '.$e->getMessage());
        }

        if ($facebookPages === []) {
            return $this->redirectAfterInstagram($returnRoute)
                ->with('error', 'Pehle Facebook Page connect karo, phir Instagram connect karo.');
        }

        return view('app.brand.instagram-page-select', [
            'brand' => $brand,
            'instagramAccounts' => $instagramAccounts,
            'pages' => $facebookPages,
            'returnRoute' => $returnRoute,
        ]);
    }

    public function storeInstagramPage(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');
        $returnRoute = session('instagram_connect_return', 'app.brand.social-accounts');

        $validated = $request->validate([
            'facebook_account_id' => ['required', 'integer'],
        ]);

        $facebookAccount = SocialAccount::query()
            ->where('id', $validated['facebook_account_id'])
            ->where('brand_id', $brand->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->first();

        if (! $facebookAccount) {
            return redirect()
                ->route('app.brand.social-accounts.instagram-pages')
                ->with('error', 'Select a valid Facebook Page.');
        }

        try {
            $account = $this->socialConnect->connectInstagram($brand, $facebookAccount);
        } catch (\Throwable $e) {
            return redirect()
                ->route('app.brand.social-accounts.instagram-pages')
                ->with('error', 'Instagram connection failed: '.$e->getMessage());
        }

        session()->forget('instagram_connect_return');

        return $this->redirectAfterInstagram($returnRoute)
            ->with('success', 'Instagram connected: '.$account->account_name.' (via '.$facebookAccount->account_name.')');
    }

    private function redirectAfterInstagram(string $returnRoute): RedirectResponse
    {
        if ($returnRoute === 'onboarding.wizard') {
            return redirect()
                ->route('onboarding.wizard')
                ->with('step', 5);
        }

        return redirect()->route('app.brand.social-accounts');
    }

    public function destroy(Request $request, SocialAccount $socialAccount): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        if ($socialAccount->brand_id !== $brand->id) {
            abort(403);
        }

        $name = $socialAccount->account_name;
        $this->socialAccounts->disconnect($socialAccount);

        return redirect()
            ->route('app.brand.social-accounts')
            ->with('success', 'Disconnected '.$name.'.');
    }
}
