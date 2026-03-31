<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Relaticle\ActivityLog\Contracts\TenantResolver;
use Relaticle\ActivityLog\Filament\Schemas\ActivityTimeline;
use Relaticle\ActivityLog\Filament\Schemas\AttributeHistory;
use Relaticle\ActivityLog\Models\Activity;
use Relaticle\ActivityLog\Policies\ActivityPolicy;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

final class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activitylog.php', 'activitylog');

        $this->app->bind(
            TenantResolver::class,
            static function (): TenantResolver {
                /** @var class-string<TenantResolver> $resolverClass */
                $resolverClass = config('activitylog.tenant_resolver');

                return new $resolverClass;
            }
        );
    }

    public function boot(): void
    {
        config()->set('activitylog.activity_model', Activity::class);

        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(SpatieActivity::class, ActivityPolicy::class);

        Livewire::component('activity-timeline', ActivityTimeline::class);
        Livewire::component('attribute-history', AttributeHistory::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'activity-log');
    }
}
