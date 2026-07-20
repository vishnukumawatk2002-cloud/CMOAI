<?php

namespace App\Application\Services\Brand;

use App\Models\BrandAsset;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PublishMediaUrlGenerator
{
    public function signedUrlForAsset(BrandAsset $asset): string
    {
        $publicBase = $this->resolvePublicBaseUrl();
        $defaultBase = rtrim((string) config('app.url'), '/');

        URL::forceRootUrl($publicBase);

        try {
            return URL::temporarySignedRoute(
                'publish-media.show',
                now()->addHours(2),
                ['asset' => $asset->id]
            );
        } finally {
            URL::forceRootUrl($defaultBase);
        }
    }

    public function resolvePublicBaseUrl(): string
    {
        foreach ($this->publicBaseCandidates() as $candidate) {
            $candidate = rtrim($candidate, '/');
            $host = parse_url($candidate, PHP_URL_HOST);

            if ($host && $this->isPublicHost($host)) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Instagram requires a public media URL. Connect Facebook for this brand, or add APP_PUBLIC_URL=https://your-live-domain.com to .env, then publish again.'
        );
    }

    /** @return list<string> */
    private function publicBaseCandidates(): array
    {
        $candidates = [];

        if ($public = config('services.publish.public_url')) {
            $candidates[] = (string) $public;
        }

        if ($appUrl = config('app.url')) {
            $candidates[] = (string) $appUrl;
        }

        if ($requestBase = $this->requestBaseUrl()) {
            $candidates[] = $requestBase;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function requestBaseUrl(): ?string
    {
        $request = request();

        if (! $request || ! $this->isPublicHost($request->getHost())) {
            return null;
        }

        $basePath = $request->getBasePath();

        return rtrim($request->getSchemeAndHttpHost().($basePath ?: ''), '/');
    }

    public function isPublicHost(?string $host): bool
    {
        if (! $host) {
            return false;
        }

        return ! in_array(strtolower($host), ['localhost', '127.0.0.1', '[::1]'], true);
    }

    public function isPublicHttpUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return $this->isPublicHost(parse_url($url, PHP_URL_HOST));
    }
}
