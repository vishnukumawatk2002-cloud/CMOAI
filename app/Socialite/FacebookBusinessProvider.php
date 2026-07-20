<?php

namespace App\Socialite;

use Laravel\Socialite\Two\FacebookProvider;

class FacebookBusinessProvider extends FacebookProvider
{
    /** @var array<int, string> */
    protected $scopes = [];

    /**
     * Keep profile fields minimal — fewer Graph calls / less rate-limit pressure.
     *
     * @var array<int, string>
     */
    protected $fields = ['id', 'name', 'email'];

    /**
     * @param  string|null  $state
     * @return array<string, string>
     */
    protected function getCodeFields($state = null): array
    {
        $configId = config('services.facebook.login_config_id');

        if (filled($configId)) {
            return [
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUrl,
                'state' => $state,
                'config_id' => $configId,
                'response_type' => 'code',
                'override_default_response_type' => 'true',
            ];
        }

        $fields = parent::getCodeFields($state);

        if ($fields['scope'] === '') {
            unset($fields['scope']);
        }

        return $fields;
    }
}
