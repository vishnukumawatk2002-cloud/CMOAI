<?php

namespace App\Application\Services\Brand;

use App\Infrastructure\Facebook\FacebookGraph;
use App\Models\Brand;
use App\Models\OauthToken;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SocialConnectService
{
    /**
     * @return array{user_token: string, expires_in: int|null, pages: list<array<string, mixed>>}
     */
    public function fetchFacebookPagesWithUserToken(string $userToken, ?int $expiresIn = null): array
    {
        $pages = [];
        $url = 'https://graph.facebook.com/v21.0/me/accounts';
        $query = [
            'access_token' => $userToken,
            'fields' => 'id,name,access_token,picture,fan_count,username',
            'limit' => 100,
        ];

        do {
            $response = FacebookGraph::http()->get($url, $query);

            if (! $response->successful()) {
                $apiMessage = $response->json('error.message');

                throw new RuntimeException(
                    $apiMessage
                        ? 'Could not fetch Facebook pages: '.$apiMessage
                        : 'Could not fetch Facebook pages. Please try again.'
                );
            }

            foreach ($response->json('data', []) as $page) {
                if (! filled($page['id'] ?? null)) {
                    continue;
                }

                $pages[] = [
                    'id' => (string) $page['id'],
                    'name' => (string) ($page['name'] ?? 'Facebook Page'),
                    'username' => $page['username'] ?? null,
                    'access_token' => (string) ($page['access_token'] ?? ''),
                    'fan_count' => (int) ($page['fan_count'] ?? 0),
                    'picture_url' => data_get($page, 'picture.data.url'),
                    'source' => 'facebook',
                ];
            }

            $next = $response->json('paging.next');
            $url = is_string($next) && $next !== '' ? $next : null;
            $query = []; // next URL already includes query string
        } while ($url);

        return [
            'user_token' => $userToken,
            'expires_in' => $expiresIn,
            'pages' => array_values($pages),
        ];
    }

    /**
     * Find a reusable Facebook user token from this brand or the user's other brands.
     *
     * @return array{user_token: string, expires_in: int|null}|null
     */
    public function resolveReusableFacebookUserToken(Brand $brand): ?array
    {
        $brandIds = Brand::query()
            ->where('user_id', $brand->user_id)
            ->pluck('id');

        $accounts = SocialAccount::query()
            ->whereIn('brand_id', $brandIds)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderByRaw('CASE WHEN brand_id = ? THEN 0 ELSE 1 END', [$brand->id])
            ->orderByDesc('connected_at')
            ->get();

        foreach ($accounts as $account) {
            $token = $account->oauthToken?->refresh_token ?: $account->oauthToken?->access_token;

            if (! filled($token) || ! $this->isUserAccessToken((string) $token)) {
                continue;
            }

            return [
                'user_token' => (string) $token,
                'expires_in' => $account->oauthToken?->expires_at
                    ? max(0, now()->diffInSeconds($account->oauthToken->expires_at, false))
                    : null,
            ];
        }

        return null;
    }

    /**
     * Pages already connected on the user's brands (including soft-deleted on current brand for reconnect).
     *
     * @return list<array<string, mixed>>
     */
    public function facebookPagesFromUserBrands(Brand $brand): array
    {
        $brandIds = Brand::query()
            ->where('user_id', $brand->user_id)
            ->pluck('id');

        return SocialAccount::withTrashed()
            ->whereIn('brand_id', $brandIds)
            ->where('platform', 'facebook')
            ->where(function ($query) use ($brand) {
                $query->whereNull('deleted_at')
                    ->orWhere('brand_id', $brand->id);
            })
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhereNotNull('deleted_at');
            })
            ->with('oauthToken')
            ->orderByDesc('connected_at')
            ->get()
            ->unique('external_id')
            ->map(function (SocialAccount $account) {
                return [
                    'id' => (string) $account->external_id,
                    'name' => (string) $account->account_name,
                    'username' => $account->account_handle,
                    'access_token' => (string) ($account->oauthToken?->access_token ?? ''),
                    'fan_count' => (int) $account->follower_count,
                    'picture_url' => $account->profile_image_url,
                    'source' => 'brand',
                    'refresh_token' => $account->oauthToken?->refresh_token,
                    'expires_at' => $account->oauthToken?->expires_at?->toIso8601String(),
                    'was_disconnected' => $account->trashed(),
                ];
            })
            ->filter(fn (array $page) => $page['id'] !== '' && $page['access_token'] !== '')
            ->values()
            ->all();
    }

    /**
     * Merge Graph pages + pages already connected on user's brands.
     *
     * @param  list<array<string, mixed>>  $graphPages
     * @param  list<array<string, mixed>>  $brandPages
     * @return list<array<string, mixed>>
     */
    public function mergeFacebookPageLists(array $graphPages, array $brandPages): array
    {
        $merged = [];

        foreach (array_merge($graphPages, $brandPages) as $page) {
            $id = (string) ($page['id'] ?? '');

            if ($id === '') {
                continue;
            }

            if (! isset($merged[$id])) {
                $merged[$id] = $page;

                continue;
            }

            // Prefer Graph API row (fresh page token), keep name/picture fallbacks.
            if (($page['source'] ?? '') === 'facebook' && filled($page['access_token'] ?? null)) {
                $merged[$id] = array_merge($merged[$id], $page);
            }
        }

        return array_values($merged);
    }

    /**
     * @return array{user_token: string, expires_in: int|null, pages: list<array<string, mixed>>}
     */
    public function fetchFacebookPagesForConnect(object $facebookUser): array
    {
        $exchanged = $this->exchangeForLongLivedUserToken($facebookUser->token);

        return $this->fetchFacebookPagesWithUserToken(
            $exchanged['token'],
            $exchanged['expires_in']
        );
    }

    /**
     * Build picker list: Graph /me/accounts + pages already connected on any of the user's brands.
     * Same Facebook Page can be linked to multiple brands (unique per brand_id + external_id).
     *
     * @return array{user_token: string, expires_in: int|null, pages: list<array<string, mixed>>}
     */
    public function buildFacebookPickerPayload(Brand $brand, ?string $userToken = null, ?int $expiresIn = null): array
    {
        $graphPages = [];

        if (filled($userToken)) {
            try {
                $payload = $this->fetchFacebookPagesWithUserToken($userToken, $expiresIn);
                $userToken = $payload['user_token'];
                $expiresIn = $payload['expires_in'];
                $graphPages = $payload['pages'];
            } catch (RuntimeException) {
                // Token may be expired; still allow reuse from other brands below.
            }
        }

        $brandPages = $this->facebookPagesFromUserBrands($brand);
        $pages = $this->mergeFacebookPageLists($graphPages, $brandPages);

        if ($pages === []) {
            if (filled($userToken)) {
                throw new RuntimeException($this->explainMissingFacebookPages($userToken));
            }

            throw new RuntimeException(
                'No Facebook Pages available. Connect Facebook on any brand first, or complete Facebook login again.'
            );
        }

        if (! filled($userToken)) {
            foreach ($brandPages as $page) {
                if (filled($page['refresh_token'] ?? null)) {
                    $userToken = (string) $page['refresh_token'];
                    break;
                }
            }
        }

        return [
            'user_token' => (string) $userToken,
            'expires_in' => $expiresIn,
            'pages' => $pages,
        ];
    }

    public function connectFacebook(Brand $brand, object $facebookUser): SocialAccount
    {
        $exchanged = $this->exchangeForLongLivedUserToken($facebookUser->token);
        $payload = $this->buildFacebookPickerPayload(
            $brand,
            $exchanged['token'],
            $exchanged['expires_in']
        );
        $page = $this->resolveFacebookPageForBrand($brand, $payload['pages']);

        return $this->storeFacebookPage(
            $brand,
            $page,
            $payload['user_token'],
            $payload['expires_in']
        );
    }

    /**
     * @param  list<string>  $pageIds
     * @param  list<array<string, mixed>>  $sessionPages
     * @return list<SocialAccount>
     */
    public function connectFacebookPages(
        Brand $brand,
        array $pageIds,
        string $userToken,
        ?int $expiresIn = null,
        array $sessionPages = []
    ): array {
        $exchanged = $this->normalizeFacebookUserToken($userToken);
        $userToken = $exchanged['token'];
        $expiresIn = $exchanged['expires_in'] ?? $expiresIn;

        $available = collect();

        try {
            $available = collect($this->fetchFacebookPagesWithUserToken($userToken, $expiresIn)['pages'])
                ->keyBy(fn (array $page) => (string) ($page['id'] ?? ''));
        } catch (RuntimeException) {
            // Fall back to session / other-brand pages.
        }

        foreach ($this->facebookPagesFromUserBrands($brand) as $page) {
            $id = (string) ($page['id'] ?? '');

            if ($id !== '' && ! $available->has($id)) {
                $available->put($id, $page);
            }
        }

        foreach ($sessionPages as $page) {
            $id = (string) ($page['id'] ?? '');

            if ($id !== '' && ! $available->has($id) && filled($page['access_token'] ?? null)) {
                $available->put($id, $page);
            }
        }

        $selectedIds = array_values(array_unique(array_map('strval', $pageIds)));
        $connected = [];

        foreach ($selectedIds as $pageId) {
            $page = $available->get($pageId);

            if (! $page || empty($page['access_token'])) {
                continue;
            }

            $refreshToken = filled($page['refresh_token'] ?? null)
                ? (string) $page['refresh_token']
                : $userToken;

            $connected[] = $this->storeFacebookPage($brand, [
                'id' => (string) $page['id'],
                'name' => (string) ($page['name'] ?? 'Facebook Page'),
                'username' => $page['username'] ?? null,
                'access_token' => (string) $page['access_token'],
                'fan_count' => (int) ($page['fan_count'] ?? 0),
                'picture_url' => $page['picture_url'] ?? data_get($page, 'picture.data.url'),
            ], $refreshToken, $expiresIn);
        }

        if ($connected === []) {
            throw new RuntimeException('Select at least one Facebook Page to connect.');
        }

        return $connected;
    }

    /** @param  array<string, mixed>  $page */
    public function storeFacebookPage(Brand $brand, array $page, string $userToken, ?int $expiresIn = null): SocialAccount
    {
        $externalId = (string) ($page['id'] ?? '');

        if ($externalId === '' || empty($page['access_token'])) {
            throw new RuntimeException('Invalid Facebook Page selected.');
        }

        $account = SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => 'facebook',
                'external_id' => $externalId,
            ],
            [
                'account_name' => (string) ($page['name'] ?? 'Facebook Page'),
                'account_handle' => $page['username'] ?? ($page['account_handle'] ?? null),
                'account_type' => 'page',
                'follower_count' => (int) ($page['fan_count'] ?? 0),
                'profile_image_url' => $page['picture_url'] ?? data_get($page, 'picture.data.url'),
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );

        OauthToken::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'access_token' => (string) $page['access_token'],
                'refresh_token' => $userToken,
                'token_type' => 'Bearer',
                'expires_at' => $this->facebookUserTokenExpiry($expiresIn),
                'scopes' => config('services.facebook.scopes'),
            ]
        );

        return $account;
    }

    /** @return array{token: string, expires_in: int|null} */
    public function normalizeFacebookUserToken(string $userToken): array
    {
        $userToken = trim($userToken);

        if ($userToken === '') {
            throw new RuntimeException('Facebook user token is missing.');
        }

        if (! $this->isUserAccessToken($userToken)) {
            throw new RuntimeException('Facebook user token is invalid. Connect Facebook again.');
        }

        return $this->exchangeForLongLivedUserToken($userToken);
    }

    public function ensureFacebookPageToken(SocialAccount $account): string
    {
        if ($account->platform !== 'facebook') {
            throw new RuntimeException('Expected a Facebook Page account.');
        }

        $account->loadMissing(['oauthToken', 'brand']);
        $oauth = $account->oauthToken;

        if (! $oauth) {
            throw new RuntimeException(
                'Facebook connection is incomplete. Go to Social accounts and connect Facebook again.'
            );
        }

        $pageId = (string) $account->external_id;
        $storedPageToken = (string) ($oauth->access_token ?: '');

        if ($storedPageToken !== '' && $this->verifyPageToken($storedPageToken, $pageId)) {
            $account->update([
                'last_synced_at' => now(),
                'status' => 'active',
            ]);

            return $storedPageToken;
        }

        $userToken = $this->resolveFacebookUserToken($account, $oauth);
        $pageToken = $this->fetchPageAccessToken($userToken, $pageId)
            ?? $this->fetchPageAccessTokenDirect($userToken, $pageId);

        if ($pageToken === null) {
            $reusable = $this->resolveReusableFacebookUserToken($account->brand);

            if ($reusable && filled($reusable['user_token']) && $reusable['user_token'] !== $userToken) {
                $pageToken = $this->fetchPageAccessToken($reusable['user_token'], $pageId)
                    ?? $this->fetchPageAccessTokenDirect($reusable['user_token'], $pageId);

                if ($pageToken !== null) {
                    $userToken = $reusable['user_token'];
                    $oauth->update([
                        'refresh_token' => $userToken,
                        'expires_at' => $this->facebookUserTokenExpiry($reusable['expires_in']),
                    ]);
                }
            }
        }

        if ($pageToken === null) {
            if ($storedPageToken !== '') {
                return $storedPageToken;
            }

            throw new RuntimeException(
                'Facebook Page access was revoked or not granted. Open Social Accounts, click Reconnect on this Page, allow all permissions once, then publish again.'
            );
        }

        $oauth->update(['access_token' => $pageToken]);
        $account->update([
            'last_synced_at' => now(),
            'status' => 'active',
        ]);

        return $pageToken;
    }

    public function refreshFacebookAccountsForBrand(Brand $brand): void
    {
        SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->get()
            ->each(function (SocialAccount $account): void {
                try {
                    $this->ensureFacebookPageToken($account);
                } catch (\Throwable) {
                    // Keep the page usable; publish will surface a clearer error if needed.
                }
            });
    }

    public function refreshFacebookPageToken(SocialAccount $account): string
    {
        return $this->ensureFacebookPageToken($account);
    }

    private function refreshFacebookUserTokenIfNeeded(OauthToken $oauth): string
    {
        $userToken = (string) ($oauth->refresh_token ?: '');

        if ($userToken === '') {
            throw new RuntimeException(
                'Facebook session expired. Go to Social accounts and reconnect Facebook.'
            );
        }

        if (! $this->isUserAccessToken($userToken)) {
            throw new RuntimeException('Stored Facebook user token is invalid.');
        }

        if ($oauth->expires_at && $oauth->expires_at->isFuture() && $oauth->expires_at->gt(now()->addDays(7))) {
            return $userToken;
        }

        $exchanged = $this->exchangeForLongLivedUserToken($userToken);

        $oauth->update([
            'refresh_token' => $exchanged['token'],
            'expires_at' => $this->facebookUserTokenExpiry($exchanged['expires_in']),
        ]);

        return $exchanged['token'];
    }

    private function resolveFacebookUserToken(SocialAccount $account, OauthToken $oauth): string
    {
        if (filled($oauth->refresh_token) && $this->isUserAccessToken((string) $oauth->refresh_token)) {
            try {
                return $this->refreshFacebookUserTokenIfNeeded($oauth);
            } catch (\Throwable) {
                // Fall back to another brand's user token below.
            }
        }

        $reusable = $this->resolveReusableFacebookUserToken($account->brand);

        if ($reusable && filled($reusable['user_token']) && $this->isUserAccessToken($reusable['user_token'])) {
            $oauth->update([
                'refresh_token' => $reusable['user_token'],
                'expires_at' => $this->facebookUserTokenExpiry($reusable['expires_in']),
            ]);

            return $this->refreshFacebookUserTokenIfNeeded($oauth->fresh());
        }

        throw new RuntimeException(
            'Facebook session expired. Go to Social accounts and reconnect Facebook.'
        );
    }

    private function fetchPageAccessTokenDirect(string $userToken, string $pageId): ?string
    {
        $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$pageId}", [
            'fields' => 'access_token',
            'access_token' => $userToken,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $pageToken = (string) ($response->json('access_token') ?? '');

        return $pageToken !== '' ? $pageToken : null;
    }

    private function verifyPageToken(string $pageToken, string $pageId): bool
    {
        try {
            $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'fields' => 'id',
                'access_token' => $pageToken,
            ]);
        } catch (\Throwable) {
            return false;
        }

        return $response->successful() && (string) $response->json('id') === $pageId;
    }

    private function isUserAccessToken(string $token): bool
    {
        $appToken = config('services.facebook.client_id').'|'.config('services.facebook.client_secret');

        try {
            $response = FacebookGraph::http()->get('https://graph.facebook.com/v21.0/debug_token', [
                'input_token' => $token,
                'access_token' => $appToken,
            ]);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        return strtoupper((string) ($response->json('data.type') ?? '')) === 'USER';
    }

    private function fetchPageAccessToken(string $userToken, string $pageId): ?string
    {
        $url = 'https://graph.facebook.com/v21.0/me/accounts';
        $query = [
            'access_token' => $userToken,
            'fields' => 'id,access_token',
            'limit' => 100,
        ];

        do {
            $response = FacebookGraph::http()->get($url, $query);

            if (! $response->successful()) {
                return null;
            }

            foreach ($response->json('data', []) as $page) {
                if ((string) ($page['id'] ?? '') !== $pageId) {
                    continue;
                }

                $pageToken = (string) ($page['access_token'] ?? '');

                return $pageToken !== '' ? $pageToken : null;
            }

            $next = $response->json('paging.next');
            $url = is_string($next) && $next !== '' ? $next : null;
            $query = [];
        } while ($url);

        return null;
    }

    /** @return array{token: string, expires_in: int|null} */
    private function exchangeForLongLivedUserToken(string $token): array
    {
        $response = FacebookGraph::http()->get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'fb_exchange_token' => $token,
        ]);

        if ($response->successful() && filled($response->json('access_token'))) {
            return [
                'token' => (string) $response->json('access_token'),
                'expires_in' => $response->json('expires_in') !== null
                    ? (int) $response->json('expires_in')
                    : null,
            ];
        }

        return ['token' => $token, 'expires_in' => null];
    }

    private function facebookUserTokenExpiry(?int $expiresIn): \Illuminate\Support\Carbon
    {
        if ($expiresIn !== null && $expiresIn > 86400) {
            return now()->addSeconds($expiresIn);
        }

        return now()->addDays(55);
    }

    /** @param  list<array<string, mixed>>  $pages */
    private function resolveFacebookPageForBrand(Brand $brand, array $pages): array
    {
        $connectedIds = SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->pluck('external_id')
            ->map(fn ($id) => (string) $id);

        // Prefer a page that is not already connected (for "Add another page").
        foreach ($pages as $page) {
            $id = (string) ($page['id'] ?? '');

            if ($id !== '' && ! $connectedIds->contains($id)) {
                return $page;
            }
        }

        return $pages[0];
    }

    public function connectInstagram(Brand $brand, SocialAccount $facebookAccount): SocialAccount
    {

    // dd($facebookAccount);
        if ($facebookAccount->brand_id !== $brand->id || $facebookAccount->platform !== 'facebook') {
            throw new RuntimeException('Select a Facebook Page that belongs to this brand.');
        }

        $facebookAccount->loadMissing('oauthToken');

        if (! $facebookAccount->oauthToken) {
            throw new RuntimeException('Connect your Facebook Page first, then connect Instagram.');
        }

        if ($facebookAccount->status !== 'active' || $facebookAccount->trashed()) {
            throw new RuntimeException('That Facebook Page is not active. Reconnect it first, then try Instagram.');
        }

        $oauth = $facebookAccount->oauthToken;
        $linked = $this->resolveInstagramForFacebookPage($brand, $facebookAccount);

        if ($linked === null || empty($linked['instagram']['id'] ?? null)) {
            $restored = $this->restoreSoftDeletedInstagram($brand, $facebookAccount);

            if ($restored) {
                return $restored;
            }
        }

        if ($linked === null) {
            throw new RuntimeException(
                '“'.$facebookAccount->account_name.'” ka Facebook token Instagram nahi de raha. Social Accounts pe us Page ka Reconnect dabao, Facebook dialog me ye Page + Instagram permissions allow karo, phir Connect Instagram try karo.'
            );
        }

        $instagram = $linked['instagram'];

        if (! is_array($instagram) || empty($instagram['id'])) {
            $userToken = $linked['user_token'] ?: $oauth->refresh_token;
            $hint = 'Meta ab “'.$facebookAccount->account_name.'” pe Instagram Business nahi de raha (pehle disconnect ke baad link/permission reset ho sakti hai). Instagram Professional account ko is Page se dubara link karo, ya Page pe Reconnect karke Instagram permissions allow karo.';

            if (filled($userToken) && ! $this->hasGrantedPermission((string) $userToken, 'instagram_basic')) {
                $hint = 'Facebook login me instagram_basic permission missing hai. “'.$facebookAccount->account_name.'” → Reconnect → Instagram permissions allow karo, phir Connect Instagram.';
            }

            throw new RuntimeException($hint);
        }

        $pageToken = $linked['page_token'] ?: $oauth->access_token;

        if (! empty($linked['page_token'])) {
            $oauth->update([
                'access_token' => $linked['page_token'],
                'refresh_token' => $linked['user_token'] ?: $oauth->refresh_token,
            ]);
        }

        $handle = isset($instagram['username']) ? '@'.$instagram['username'] : null;

        $account = SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => 'instagram',
                'external_id' => (string) $instagram['id'],
            ],
            [
                'account_name' => $instagram['name'] ?? $instagram['username'] ?? 'Instagram',
                'account_handle' => $handle,
                'account_type' => 'profile',
                'follower_count' => (int) ($instagram['followers_count'] ?? 0),
                'profile_image_url' => $instagram['profile_picture_url'] ?? null,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );

        OauthToken::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'access_token' => $pageToken,
                'token_type' => 'Bearer',
                'expires_at' => $oauth->expires_at,
                'scopes' => config('services.facebook.scopes'),
            ]
        );

        return $account;
    }

    /**
     * Reconnect a previously disconnected Instagram account if Page token still works.
     */
    private function restoreSoftDeletedInstagram(Brand $brand, SocialAccount $facebookAccount): ?SocialAccount
    {
        $pageToken = $facebookAccount->oauthToken?->access_token
            ?: $facebookAccount->oauthToken?->refresh_token;

        if (! filled($pageToken)) {
            return null;
        }

        $candidates = SocialAccount::onlyTrashed()
            ->where('brand_id', $brand->id)
            ->where('platform', 'instagram')
            ->with('oauthToken')
            ->orderByDesc('deleted_at')
            ->get();

        foreach ($candidates as $igAccount) {
            $token = $igAccount->oauthToken?->access_token ?: $pageToken;

            try {
                $response = FacebookGraph::http()->get('https://graph.facebook.com/v21.0/'.$igAccount->external_id, [
                    'fields' => 'id,username,name,profile_picture_url,followers_count',
                    'access_token' => $token,
                ]);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful() || empty($response->json('id'))) {
                // Retry with the selected Facebook Page token.
                if ($token === $pageToken) {
                    continue;
                }

                try {
                    $response = FacebookGraph::http()->get('https://graph.facebook.com/v21.0/'.$igAccount->external_id, [
                        'fields' => 'id,username,name,profile_picture_url,followers_count',
                        'access_token' => $pageToken,
                    ]);
                } catch (\Throwable) {
                    continue;
                }

                if (! $response->successful() || empty($response->json('id'))) {
                    continue;
                }
            }

            $ig = $response->json();
            $igAccount->restore();
            $igAccount->forceFill([
                'account_name' => $ig['name'] ?? $ig['username'] ?? $igAccount->account_name,
                'account_handle' => isset($ig['username']) ? '@'.$ig['username'] : $igAccount->account_handle,
                'follower_count' => (int) ($ig['followers_count'] ?? $igAccount->follower_count),
                'profile_image_url' => $ig['profile_picture_url'] ?? $igAccount->profile_image_url,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
            ])->save();

            OauthToken::query()->updateOrCreate(
                ['social_account_id' => $igAccount->id],
                [
                    'access_token' => $pageToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $facebookAccount->oauthToken?->expires_at,
                    'scopes' => config('services.facebook.scopes'),
                ]
            );

            return $igAccount->fresh(['oauthToken']);
        }

        return null;
    }

    /**
     * Instagram accounts discoverable from this brand's Facebook Pages.
     * Uses Page tokens only (1 Graph call per Page) to avoid Facebook rate limits.
     *
     * @return list<array<string, mixed>>
     */
    public function discoverInstagramAccountsForBrand(Brand $brand): array
    {
        $connectedIgIds = SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('platform', 'instagram')
            ->where('status', 'active')
            ->pluck('external_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $facebookPages = SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderBy('account_name')
            ->get();

        $discovered = [];

        foreach ($facebookPages as $facebookPage) {
            $pageToken = $facebookPage->oauthToken?->access_token;

            if (! filled($pageToken)) {
                continue;
            }

            $linked = $this->resolveLinkedInstagramViaPage(
                (string) $facebookPage->external_id,
                (string) $pageToken
            );
            $ig = is_array($linked['instagram'] ?? null) ? $linked['instagram'] : null;

            if (! is_array($ig) || empty($ig['id'])) {
                continue;
            }

            $igId = (string) $ig['id'];

            if (isset($discovered[$igId])) {
                continue;
            }

            $discovered[$igId] = [
                'facebook_account_id' => $facebookPage->id,
                'facebook_page_name' => (string) $facebookPage->account_name,
                'instagram_id' => $igId,
                'name' => (string) ($ig['name'] ?? $ig['username'] ?? 'Instagram'),
                'username' => $ig['username'] ?? null,
                'followers_count' => (int) ($ig['followers_count'] ?? 0),
                'picture_url' => $ig['profile_picture_url'] ?? null,
                'already_connected' => in_array($igId, $connectedIgIds, true),
            ];
        }

        return array_values($discovered);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function facebookPagesForInstagramPicker(Brand $brand): array
    {
        return SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderBy('account_name')
            ->get()
            ->map(fn (SocialAccount $page) => [
                'social_account_id' => $page->id,
                'id' => (string) $page->external_id,
                'name' => (string) $page->account_name,
                'username' => $page->account_handle,
                'fan_count' => (int) $page->follower_count,
                'picture_url' => $page->profile_image_url,
                'has_token' => filled($page->oauthToken?->access_token) || filled($page->oauthToken?->refresh_token),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{instagram: array<string, mixed>|null, page_token: string|null, user_token: string|null}|null
     */
    private function resolveInstagramForFacebookPage(Brand $brand, SocialAccount $facebookAccount): ?array
    {
        $pageId = (string) $facebookAccount->external_id;
        $facebookAccount->loadMissing('oauthToken');

        $ownUser = $facebookAccount->oauthToken?->refresh_token;
        $ownPage = $facebookAccount->oauthToken?->access_token;
        $pageOnlyLinked = null;

        // Prefer Page token first — 1 light call, avoids /me/accounts rate limits.
        if (filled($ownPage)) {
            $linked = $this->resolveLinkedInstagramViaPage($pageId, (string) $ownPage);

            if ($linked !== null && ! empty($linked['instagram']['id'] ?? null)) {
                return [
                    'instagram' => $linked['instagram'],
                    'page_token' => $linked['page_token'] ?: (string) $ownPage,
                    'user_token' => $ownUser ? (string) $ownUser : null,
                ];
            }

            // Page reachable but no IG field yet — keep for clearer errors later.
            if ($linked !== null) {
                $pageOnlyLinked = [
                    'instagram' => $linked['instagram'],
                    'page_token' => $linked['page_token'] ?: (string) $ownPage,
                    'user_token' => $ownUser ? (string) $ownUser : null,
                ];
            }
        }

        $candidateUserTokens = [];

        if (filled($ownUser)) {
            $candidateUserTokens[] = (string) $ownUser;
        }

        foreach (SocialAccount::query()
            ->where('brand_id', $brand->id)
            ->where('platform', 'facebook')
            ->where('status', 'active')
            ->with('oauthToken')
            ->get() as $account) {
            $token = $account->oauthToken?->refresh_token;

            if (filled($token) && ! in_array((string) $token, $candidateUserTokens, true)) {
                $candidateUserTokens[] = (string) $token;
            }
        }

        foreach ($candidateUserTokens as $userToken) {
            $linked = $this->resolveLinkedInstagram($userToken, $pageId);

            if ($linked !== null && ! empty($linked['instagram']['id'] ?? null)) {
                return [
                    'instagram' => $linked['instagram'],
                    'page_token' => $linked['page_token'],
                    'user_token' => $userToken,
                ];
            }

            if ($linked !== null && ! isset($pageOnlyLinked)) {
                $pageOnlyLinked = [
                    'instagram' => $linked['instagram'],
                    'page_token' => $linked['page_token'],
                    'user_token' => $userToken,
                ];
            }
        }

        return $pageOnlyLinked ?? null;
    }

    /** @var array<string, list<array<string, mixed>>|null> */
    private array $facebookAccountsCache = [];

    /** @return array{instagram: array<string, mixed>|null, page_token: string|null}|null */
    private function resolveLinkedInstagram(string $userToken, string $pageId): ?array
    {
        $cacheKey = hash('sha256', $userToken);

        if (! array_key_exists($cacheKey, $this->facebookAccountsCache)) {
            $this->facebookAccountsCache[$cacheKey] = $this->fetchFacebookAccountsWithInstagram($userToken);
        }

        $pages = $this->facebookAccountsCache[$cacheKey];

        if ($pages === null) {
            return null;
        }

        foreach ($pages as $page) {
            if ((string) ($page['id'] ?? '') !== (string) $pageId) {
                continue;
            }

            return [
                'instagram' => $this->extractInstagramFromPageNode($page),
                'page_token' => $page['access_token'] ?? null,
            ];
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>|null  null = request failed / rate limited
     */
    private function fetchFacebookAccountsWithInstagram(string $userToken): ?array
    {
        $pages = [];

        try {
            $url = 'https://graph.facebook.com/v21.0/me/accounts';
            $query = [
                'access_token' => $userToken,
                'fields' => 'id,name,access_token,instagram_business_account{id,username,name,profile_picture_url,followers_count},connected_instagram_account{id,username,name,profile_picture_url,followers_count}',
                'limit' => 100,
            ];

            do {
                $response = FacebookGraph::http()->get($url, $query);

                if (! $response->successful()) {
                    return null;
                }

                foreach ($response->json('data', []) as $page) {
                    $pages[] = $page;
                }

                $next = $response->json('paging.next');
                $url = is_string($next) && $next !== '' ? $next : null;
                $query = [];
            } while ($url);
        } catch (\Throwable) {
            return null;
        }

        return $pages;
    }

    /** @return array{instagram: array<string, mixed>|null, page_token: string|null}|null */
    private function resolveLinkedInstagramViaPage(string $pageId, string $pageToken): ?array
    {
        try {
            $response = FacebookGraph::http()->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'fields' => 'instagram_business_account{id,username,name,profile_picture_url,followers_count},connected_instagram_account{id,username,name,profile_picture_url,followers_count}',
                'access_token' => $pageToken,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return [
            'instagram' => $this->extractInstagramFromPageNode($response->json() ?? []),
            'page_token' => $pageToken,
        ];
    }

    /** @param  array<string, mixed>  $page */
    private function extractInstagramFromPageNode(array $page): ?array
    {
        foreach (['instagram_business_account', 'connected_instagram_account'] as $key) {
            $ig = $page[$key] ?? null;

            if (is_array($ig) && ! empty($ig['id'])) {
                return $ig;
            }
        }

        $backed = $page['page_backed_instagram_accounts']['data'][0] ?? null;

        if (is_array($backed) && ! empty($backed['id'])) {
            return $backed;
        }

        return null;
    }

    private function explainMissingFacebookPages(string $userToken): string
    {
        if (! $this->hasGrantedPermission($userToken, 'pages_show_list')) {
            return 'Facebook did not grant Page access. Disconnect Facebook, then connect again and approve pages_show_list, pages_read_engagement, pages_manage_posts, and pages_manage_metadata. If using Meta Login for Business, add these permissions to your Configuration (FACEBOOK_LOGIN_CONFIG_ID).';
        }

        return 'No Facebook pages found on this account. Create a Page at facebook.com/pages/create, ensure you are Page Admin (not just Editor on a personal profile), then connect again using the same Facebook account that manages the Page.';
    }

    private function hasGrantedPermission(string $userToken, string $permission): bool
    {
        $response = FacebookGraph::http()->get('https://graph.facebook.com/v21.0/me/permissions', [
            'access_token' => $userToken,
        ]);

        if (! $response->successful()) {
            return false;
        }

        foreach ($response->json('data', []) as $entry) {
            if (($entry['permission'] ?? '') === $permission && ($entry['status'] ?? '') === 'granted') {
                return true;
            }
        }

        return false;
    }

    public function connectX(Brand $brand, object $xUser): SocialAccount
    {
        $handle = filled($xUser->nickname ?? null) ? '@'.$xUser->nickname : null;

        $account = SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => 'x',
                'external_id' => (string) $xUser->id,
            ],
            [
                'account_name' => filled($xUser->name ?? null) ? $xUser->name : ($xUser->nickname ?? 'X account'),
                'account_handle' => $handle,
                'account_type' => 'profile',
                'profile_image_url' => $xUser->avatar ?? null,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );

        OauthToken::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'access_token' => $xUser->token,
                'refresh_token' => $xUser->refreshToken ?? null,
                'token_type' => 'Bearer',
                'expires_at' => $xUser->expiresIn
                    ? now()->addSeconds((int) $xUser->expiresIn)
                    : now()->addHours(2),
                'scopes' => config('services.x.scopes'),
            ]
        );

        return $account;
    }

    public function connectLinkedIn(Brand $brand, object $linkedInUser): SocialAccount
    {
        $account = SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => 'linkedin',
                'external_id' => (string) $linkedInUser->id,
            ],
            [
                'account_name' => filled($linkedInUser->name ?? null) ? $linkedInUser->name : 'LinkedIn profile',
                'account_handle' => filled($linkedInUser->email ?? null) ? $linkedInUser->email : null,
                'account_type' => 'profile',
                'profile_image_url' => $linkedInUser->avatar ?? null,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );

        OauthToken::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'access_token' => $linkedInUser->token,
                'refresh_token' => $linkedInUser->refreshToken ?? null,
                'token_type' => 'Bearer',
                'expires_at' => $linkedInUser->expiresIn
                    ? now()->addSeconds((int) $linkedInUser->expiresIn)
                    : now()->addDays(55),
                'scopes' => config('services.linkedin-openid.scopes'),
            ]
        );

        return $account;
    }

    public function connectYouTube(Brand $brand, object $googleUser): SocialAccount
    {
        $token = (string) ($googleUser->token ?? '');

        if ($token === '') {
            throw new RuntimeException('YouTube authorization did not return an access token.');
        }

        $response = Http::withToken($token)->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'snippet,statistics',
            'mine' => 'true',
        ]);

        if (! $response->successful()) {
            $message = (string) ($response->json('error.message') ?? '');

            throw new RuntimeException(
                $message !== ''
                    ? 'Could not load YouTube channel: '.$message
                    : 'Could not load YouTube channel. Enable YouTube Data API v3 in Google Cloud Console for this OAuth app.'
            );
        }

        $channel = $response->json('items.0');

        if (! is_array($channel) || empty($channel['id'])) {
            throw new RuntimeException('No YouTube channel found on this Google account. Create a channel at youtube.com first.');
        }

        $channelId = (string) $channel['id'];
        $snippet = $channel['snippet'] ?? [];
        $stats = $channel['statistics'] ?? [];
        $thumbnails = $snippet['thumbnails'] ?? [];
        $handle = (string) ($snippet['customUrl'] ?? '');

        $account = SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => 'youtube',
                'external_id' => $channelId,
            ],
            [
                'account_name' => (string) ($snippet['title'] ?? 'YouTube channel'),
                'account_handle' => $handle !== '' ? '@'.ltrim($handle, '@') : null,
                'account_type' => 'channel',
                'follower_count' => (int) ($stats['subscriberCount'] ?? 0),
                'profile_image_url' => $thumbnails['default']['url'] ?? $thumbnails['medium']['url'] ?? null,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );

        OauthToken::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'access_token' => $token,
                'refresh_token' => $googleUser->refreshToken ?? null,
                'token_type' => 'Bearer',
                'expires_at' => $googleUser->expiresIn
                    ? now()->addSeconds((int) $googleUser->expiresIn)
                    : now()->addHour(),
                'scopes' => config('services.youtube.scopes'),
            ]
        );

        return $account;
    }

    public function connectSnapchat(Brand $brand, object $snapUser): SocialAccount
    {
        $profileId = (string) ($snapUser->id ?? data_get($snapUser->user ?? [], 'id', ''));

        if ($profileId === '') {
            throw new RuntimeException('Snapchat did not return a Public Profile ID.');
        }

        $displayName = (string) ($snapUser->name ?? 'Snapchat');
        $username = (string) ($snapUser->nickname ?? '');
        $avatar = $snapUser->avatar ?? null;
        $raw = method_exists($snapUser, 'getRaw') ? $snapUser->getRaw() : [];
        $subscriberCount = (int) ($raw['subscriber_count'] ?? 0);

        $account = SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => 'snapchat',
                'external_id' => $profileId,
            ],
            [
                'account_name' => $displayName,
                'account_handle' => $username !== '' ? '@'.$username : null,
                'account_type' => 'profile',
                'follower_count' => $subscriberCount,
                'profile_image_url' => is_string($avatar) ? $avatar : null,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );

        OauthToken::query()->updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'access_token' => (string) ($snapUser->token ?? ''),
                'refresh_token' => $snapUser->refreshToken ?? null,
                'token_type' => 'Bearer',
                'expires_at' => $snapUser->expiresIn
                    ? now()->addSeconds((int) $snapUser->expiresIn)
                    : now()->addMinutes(30),
                'scopes' => config('services.snapchat.scopes'),
            ]
        );

        return $account;
    }

    public function refreshYouTubeToken(SocialAccount $account): string
    {
        $oauth = $account->oauthToken;

        if (! $oauth?->refresh_token) {
            throw new RuntimeException('YouTube session expired. Go to Social accounts → YouTube Shorts → Reconnect.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $oauth->refresh_token,
        ]);

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            throw new RuntimeException('YouTube session expired. Go to Social accounts → YouTube Shorts → Reconnect.');
        }

        $oauth->update([
            'access_token' => (string) $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token') ?: $oauth->refresh_token,
            'expires_at' => $response->json('expires_in')
                ? now()->addSeconds((int) $response->json('expires_in'))
                : now()->addHour(),
        ]);

        return $oauth->access_token;
    }

    public function refreshSnapchatToken(SocialAccount $account): string
    {
        $oauth = $account->oauthToken;

        if (! $oauth?->refresh_token) {
            throw new RuntimeException('Snapchat session expired. Go to Social accounts → Snapchat → Reconnect.');
        }

        $response = Http::asForm()->post('https://accounts.snapchat.com/login/oauth2/access_token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $oauth->refresh_token,
            'client_id' => config('services.snapchat.client_id'),
            'client_secret' => config('services.snapchat.client_secret'),
        ]);

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            throw new RuntimeException('Snapchat session expired. Go to Social accounts → Snapchat → Reconnect.');
        }

        $oauth->update([
            'access_token' => (string) $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token') ?: $oauth->refresh_token,
            'expires_at' => $response->json('expires_in')
                ? now()->addSeconds((int) $response->json('expires_in'))
                : now()->addMinutes(30),
        ]);

        return $oauth->access_token;
    }

    public function refreshXToken(SocialAccount $account): string
    {
        $oauth = $account->oauthToken;

        if (! $oauth?->refresh_token) {
            throw new RuntimeException('X session expired. Go to Social accounts → disconnect X → connect again.');
        }

        $response = Http::asForm()
            ->withBasicAuth(
                (string) config('services.x.client_id'),
                (string) config('services.x.client_secret')
            )
            ->post('https://api.x.com/2/oauth2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $oauth->refresh_token,
                'client_id' => config('services.x.client_id'),
            ]);

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            throw new RuntimeException('X session expired. Go to Social accounts → disconnect X → connect again.');
        }

        $oauth->update([
            'access_token' => (string) $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token') ?: $oauth->refresh_token,
            'expires_at' => $response->json('expires_in')
                ? now()->addSeconds((int) $response->json('expires_in'))
                : now()->addHours(2),
        ]);

        return $oauth->access_token;
    }

    public function refreshLinkedInToken(SocialAccount $account): string
    {
        $oauth = $account->oauthToken;

        if (! $oauth?->refresh_token) {
            if ($oauth?->access_token) {
                return $oauth->access_token;
            }

            throw new RuntimeException('LinkedIn session expired. Go to Social accounts → disconnect LinkedIn → connect again.');
        }

        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $oauth->refresh_token,
            'client_id' => config('services.linkedin-openid.client_id'),
            'client_secret' => config('services.linkedin-openid.client_secret'),
        ]);

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            throw new RuntimeException('LinkedIn session expired. Go to Social accounts → disconnect LinkedIn → connect again.');
        }

        $oauth->update([
            'access_token' => (string) $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token') ?: $oauth->refresh_token,
            'expires_at' => $response->json('expires_in')
                ? now()->addSeconds((int) $response->json('expires_in'))
                : now()->addDays(55),
        ]);

        return $oauth->access_token;
    }

    public function ensureFreshToken(SocialAccount $account): string
    {
        $oauth = $account->oauthToken;

        if (! $oauth?->access_token) {
            throw new RuntimeException('Social account connection is incomplete. Reconnect in Social accounts.');
        }

        if ($oauth->expires_at && $oauth->expires_at->isPast()) {
            return match ($account->platform) {
                'facebook' => $this->ensureFacebookPageToken($account),
                'x' => $this->refreshXToken($account),
                'linkedin' => $this->refreshLinkedInToken($account),
                'youtube' => $this->refreshYouTubeToken($account),
                'snapchat' => $this->refreshSnapchatToken($account),
                default => $oauth->access_token,
            };
        }

        if ($account->platform === 'facebook') {
            if (
                ! $oauth->expires_at
                || $oauth->expires_at->isPast()
                || $oauth->expires_at->lte(now()->addDays(7))
                || ! $account->last_synced_at
                || $account->last_synced_at->lte(now()->subHours(12))
            ) {
                return $this->ensureFacebookPageToken($account);
            }

            return $oauth->access_token;
        }

        if ($account->platform === 'youtube' && $oauth->expires_at && $oauth->expires_at->lte(now()->addMinutes(5))) {
            return $this->refreshYouTubeToken($account);
        }

        if ($account->platform === 'snapchat' && $oauth->expires_at && $oauth->expires_at->lte(now()->addMinutes(5))) {
            return $this->refreshSnapchatToken($account);
        }

        return $oauth->access_token;
    }
}
