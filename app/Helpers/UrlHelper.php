<?php

declare(strict_types=1);

namespace App\Helpers;

final class UrlHelper
{
    /**
     * Get the app subdomain URL with the given path
     *
     * @param  string  $path  The path to append to the app subdomain URL
     * @return string The complete app subdomain URL
     */
    public static function getAppUrl(string $path = ''): string
    {
        $baseUrl = config('app.url');
        $scheme = parse_url($baseUrl)['scheme'] ?? 'https';
        $host = 'app.'.parse_url($baseUrl)['host'];

        return $scheme.'://'.$host.'/'.ltrim($path, '/');
    }
}
