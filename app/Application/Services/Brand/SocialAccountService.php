<?php

namespace App\Application\Services\Brand;

use App\Models\Brand;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SocialAccountService
{
    /** @return array<string, array<string, mixed>> */
    public function platformDefinitions(): array
    {
        return [
            'linkedin' => [
                'label' => 'LinkedIn',
                'icon' => 'ti-brand-linkedin',
                'color' => '#5BA4D9',
                'oauth' => true,
                'account_type' => 'page',
                'add_title' => 'Add another LinkedIn page',
                'add_sub' => 'Connect a personal profile or another company page',
            ],
            'instagram' => [
                'label' => 'Instagram',
                'icon' => 'ti-brand-instagram',
                'color' => '#E1306C',
                'oauth' => true,
                'account_type' => 'profile',
                'add_title' => 'Add another Instagram account',
                'add_sub' => 'Business account linked to your Facebook Page',
            ],
            'facebook' => [
                'label' => 'Facebook',
                'icon' => 'ti-brand-facebook',
                'color' => '#1877F2',
                'oauth' => true,
                'account_type' => 'page',
                'add_title' => 'Add another Facebook page',
                'add_sub' => 'Connect an additional page or group',
            ],
            'x' => [
                'label' => 'X / Twitter',
                'icon' => 'ti-brand-x',
                'color' => '#111827',
                'oauth' => true,
                'account_type' => 'profile',
                'add_title' => 'Add another X account',
                'add_sub' => 'Connect a personal or brand account',
            ],
            'youtube' => [
                'label' => 'YouTube',
                'icon' => 'ti-brand-youtube',
                'color' => '#FF0000',
                'oauth' => true,
                'account_type' => 'channel',
                'add_title' => 'Add another YouTube channel',
                'add_sub' => 'Connect via Google to publish posts, Shorts & carousels',
            ],
            // Snapchat — hidden for now; add back to primaryPlatformKeys() when ready.
            // 'snapchat' => [
            //     'label' => 'Snapchat',
            //     'icon' => 'ti-brand-snapchat',
            //     'color' => '#FFFC00',
            //     'oauth' => true,
            //     'account_type' => 'profile',
            //     'add_title' => 'Add another Snapchat account',
            //     'add_sub' => 'Connect Public Profile via Snapchat OAuth',
            // ],
            'pinterest' => [
                'label' => 'Pinterest',
                'icon' => 'ti-brand-pinterest',
                'color' => '#E60023',
                'oauth' => false,
                'account_type' => 'profile',
                'add_sub' => 'Auto-pin images and content',
            ],
            'threads' => [
                'label' => 'Threads',
                'icon' => 'ti-brand-threads',
                'color' => '#111827',
                'oauth' => false,
                'account_type' => 'profile',
                'add_sub' => 'Publish threads via Instagram',
            ],
            'google_business' => [
                'label' => 'Google Business',
                'icon' => 'ti-brand-google',
                'color' => '#4285F4',
                'oauth' => false,
                'account_type' => 'business',
                'add_sub' => 'Post to your GMB profile',
            ],
        ];
    }

    /** @return list<string> */
    public function primaryPlatformKeys(): array
    {
        return ['linkedin', 'instagram', 'facebook', 'x', 'youtube'];
        // 'snapchat' — uncomment when Snapchat is ready
    }

    /** @return list<string> */
    public function availablePlatformKeys(): array
    {
        // return ['youtube', 'pinterest', 'threads', 'google_business'];

        return [];
    }

    /** @return Collection<string, Collection<int, SocialAccount>> */
    public function groupByPlatform(Collection $accounts): Collection
    {
        return $accounts->groupBy('platform');
    }

    /** @return array<string, int|float|string> */
    public function summaryStats(Brand $brand, Collection $accounts): array
    {
        $active = $accounts->filter(fn (SocialAccount $a) => $this->displayStatus($a) === 'active')->count();
        $expired = $accounts->filter(fn (SocialAccount $a) => $this->displayStatus($a) === 'expired')->count();

        $accountIds = $accounts->pluck('id');
        $postsPublished = $accountIds->isEmpty()
            ? 0
            : ScheduledPost::query()
                ->whereIn('social_account_id', $accountIds)
                ->where('status', 'published')
                ->count();

        $reach = $accounts->sum('follower_count');

        return [
            'connected' => $active,
            'needs_reconnection' => $expired,
            'posts_published' => $postsPublished,
            'total_reach' => $this->formatReach($reach),
        ];
    }

    public function displayStatus(SocialAccount $account): string
    {
        if ($account->status === 'expired' || $account->status === 'error') {
            return 'expired';
        }

        if ($account->status === 'disconnected') {
            return 'disconnected';
        }

        $token = $account->oauthToken;

        if ($token?->expires_at && $token->expires_at->isPast()) {
            if ($account->platform === 'facebook' && filled($token->refresh_token)) {
                return 'active';
            }

            return 'expired';
        }

        return 'active';
    }

    public function postsPublishedCount(SocialAccount $account): int
    {
        return ScheduledPost::query()
            ->where('social_account_id', $account->id)
            ->where('status', 'published')
            ->count();
    }

    public function connectDemo(Brand $brand, string $platform): SocialAccount
    {
        $definitions = $this->platformDefinitions();

        if (! isset($definitions[$platform])) {
            throw new \InvalidArgumentException('Unsupported platform.');
        }

        $meta = $definitions[$platform];
        $slug = Str::slug($brand->name) ?: 'brand';

        return SocialAccount::withTrashed()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'platform' => $platform,
                'external_id' => 'demo-'.$platform.'-'.$brand->id,
            ],
            [
                'account_name' => $brand->name,
                'account_handle' => $platform === 'instagram' || $platform === 'x'
                    ? '@'.$slug
                    : $brand->name,
                'account_type' => $meta['account_type'],
                'follower_count' => 0,
                'status' => 'active',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'deleted_at' => null,
            ]
        );
    }

    public function disconnect(SocialAccount $account): void
    {
        // Keep Instagram OAuth so "Connect Instagram" can restore after soft-delete.
        if ($account->platform !== 'instagram') {
            $account->oauthToken?->delete();
        }

        $account->update(['status' => 'disconnected']);
        $account->delete();
    }

    public function accountSubtitle(SocialAccount $account): string
    {
        $type = match ($account->account_type) {
            'page' => 'Page',
            'profile' => $account->platform === 'snapchat' ? 'Snapchat account' : 'Personal account',
            'channel' => 'YouTube channel',
            'business' => 'Business profile',
            'group' => 'Group',
            default => ucfirst($account->account_type ?? 'Account'),
        };

        $followers = $account->follower_count > 0
            ? ' · '.number_format($account->follower_count).' followers'
            : '';

        return $type.$followers;
    }

    public function avatarInitials(SocialAccount $account): string
    {
        $name = $account->account_name ?: $account->account_handle ?: 'SA';

        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->join('') ?: 'SA';
    }

    public function avatarStyle(string $platform): string
    {
        return match ($platform) {
            'linkedin' => 'background:linear-gradient(135deg,#0A66C2,#1a85e0)',
            'instagram' => 'background:linear-gradient(135deg,#F77737,#E1306C,#C13584)',
            'facebook' => 'background:#1877F2',
            'x' => 'background:#F8F9FC;color:#111827;border:1px solid var(--border2)',
            'youtube' => 'background:#FF0000',
            'snapchat' => 'background:#FFFC00;color:#111827;border:1px solid var(--border2)',
            'pinterest' => 'background:#E60023',
            'threads' => 'background:#111827',
            'google_business' => 'background:#4285F4',
            default => 'background:var(--purple)',
        };
    }

    public function isOAuthConfigured(string $platform): bool
    {
        return match ($platform) {
            'facebook', 'instagram' => filled(config('services.facebook.client_id')),
            'x' => filled(config('services.x.client_id')),
            'linkedin' => filled(config('services.linkedin-openid.client_id')),
            'youtube' => filled(config('services.youtube.client_id')),
            'snapchat' => filled(config('services.snapchat.client_id')),
            default => false,
        };
    }

    public function supportsOAuth(string $platform): bool
    {
        return ($this->platformDefinitions()[$platform]['oauth'] ?? false) === true;
    }

    private function formatReach(int $reach): string
    {
        if ($reach >= 1_000_000) {
            return round($reach / 1_000_000, 1).'M';
        }

        if ($reach >= 1_000) {
            return round($reach / 1_000, 1).'K';
        }

        return (string) $reach;
    }
}
