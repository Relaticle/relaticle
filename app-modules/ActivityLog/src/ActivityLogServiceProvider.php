<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Relaticle\ActivityLog\Filament\Schemas\ActivityTimeline;

final class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activitylog.php', 'activitylog');
    }

    public function boot(): void
    {
        $this->app['config']->set(
            'activitylog',
            array_merge(
                $this->app['config']->get('activitylog', []),
                require __DIR__.'/../config/activitylog.php',
            ),
        );

        Livewire::component('activity-timeline', ActivityTimeline::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'activity-log');
    }
}
