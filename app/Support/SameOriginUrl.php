<?php

declare(strict_types=1);

namespace App\Support;

final class SameOriginUrl
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

        $url = $request->getSchemeAndHttpHost().$parsed['path'];

        if (isset($parsed['query'])) {
            $url .= '?'.$parsed['query'];
        }

        return $url;
    }
}
