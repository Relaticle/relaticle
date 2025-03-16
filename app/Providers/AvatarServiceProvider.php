<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AvatarService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\ServiceProvider;

final class AvatarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AvatarService::class, fn ($app): \App\Services\AvatarService => new AvatarService(
            $app->make(Cache::class),
            config('avatar.cache_ttl'),
            config('avatar.default_text_color'),
            config('avatar.default_background_color'),
            config('avatar.background_colors', [])
        ));
    }
}
