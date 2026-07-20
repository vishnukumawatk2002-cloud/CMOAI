<?php

namespace App\Socialite;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class SnapchatProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopeSeparator = ' ';

    /** @var list<string> */
    protected $scopes = ['snapchat-profile-api'];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.snapchat.com/login/oauth2/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://accounts.snapchat.com/login/oauth2/access_token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://businessapi.snapchat.com/v1/public_profiles/my_profile', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $profile = data_get($data, 'public_profile')
            ?? data_get($data, 'public_profiles.0.public_profile')
            ?? [];

        if (! is_array($profile) || empty($profile['id'])) {
            throw new \RuntimeException(
                'Snapchat Public Profile not found. Create a Public Profile in Snapchat Ads Manager and ensure your app is allowlisted for snapchat-profile-api.'
            );
        }

        return $profile;
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => (string) Arr::get($user, 'id', ''),
            'name' => (string) Arr::get($user, 'display_name', 'Snapchat'),
            'nickname' => (string) Arr::get($user, 'snap_user_name', ''),
            'email' => null,
            'avatar' => Arr::get($user, 'logo_urls.manage_profile_logo_url')
                ?? Arr::get($user, 'logo_urls.original_logo_url'),
        ]);
    }
}
