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
            $scheme = parse_url((string) $baseUrl)['scheme'] ?? 'https';
            $host = 'app.'.parse_url((string) $baseUrl)['host'];

            return $scheme.'://'.$host.'/'.ltrim($path, '/');
        });
    }
}
