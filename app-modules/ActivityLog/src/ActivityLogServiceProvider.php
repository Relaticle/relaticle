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
        Livewire::component('activity-timeline', ActivityTimeline::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'activity-log');
    }
}
