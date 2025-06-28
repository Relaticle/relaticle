<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        URL::macro('getAppUrl', function (string $path = ''): string {
            $baseUrl = config('app.url');
            $parsed = parse_url((string) $baseUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = 'app.'.($parsed['host'] ?? 'localhost');

            return $scheme.'://'.$host.'/'.ltrim($path, '/');
        });

        URL::macro('getPublicUrl', function (string $path = ''): string {
            $baseUrl = config('app.url');
            $parsed = parse_url((string) $baseUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = $parsed['host'] ?? 'localhost';

            return $scheme.'://'.$host.'/'.ltrim($path, '/');
        });
    }
}
