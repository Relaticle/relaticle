<?php

declare(strict_types=1);

namespace App\Support;

final readonly class SameOriginUrl
{
    public static function rewrite(string $absoluteUrl): string
    {
        if (! app()->bound('request')) {
            return $absoluteUrl;
        }

        $request = resolve('request');
        $host = $request->getHost();

        if ($host === '' || $host === 'localhost') {
            return $absoluteUrl;
        }

        $parsed = parse_url($absoluteUrl);

        if ($parsed === false || ! isset($parsed['path']) || blank($parsed['path'])) {
            return $absoluteUrl;
        }

        $sourceHost = $parsed['host'] ?? null;

        if ($sourceHost !== null) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if (! is_string($appHost) || $sourceHost !== $appHost) {
                return $absoluteUrl;
            }
        }

        $url = $request->getSchemeAndHttpHost().$parsed['path'];

        if (isset($parsed['query'])) {
            $url .= '?'.$parsed['query'];
        }

        return $url;
    }
}
