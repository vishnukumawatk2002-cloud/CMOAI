<?php

namespace App\Socialite;

use Laravel\Socialite\Two\GoogleProvider;

class YouTubeProvider extends GoogleProvider
{
    protected $scopeSeparator = ' ';

    /** @var list<string> */
    protected $scopes = [
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/userinfo.profile',
    ];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.google.com/o/oauth2/v2/auth', $state);
    }
}
