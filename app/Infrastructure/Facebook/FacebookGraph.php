<?php

namespace App\Infrastructure\Facebook;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class FacebookGraph
{
    public static function http(): PendingRequest
    {
        $client = Http::acceptJson()->timeout(120);

        if (config('app.env') === 'local' && ! filter_var(env('HTTP_VERIFY_SSL', true), FILTER_VALIDATE_BOOL)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}
