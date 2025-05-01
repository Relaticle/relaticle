<?php

declare(strict_types=1);

namespace Relaticle\Documentation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DocumentationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->publishResources();
    }

    /**
     * Register the module routes.
     */
    private function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
    }

    /**
     * Register the module views.
     */
    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'documentation');
    }

    /**
     * Publish resources for the module.
     */
    private function publishResources(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/documentation'),
            ], 'documentation-views');

            $this->publishes([
                __DIR__.'/../resources/markdown' => resource_path('markdown/vendor/documentation'),
            ], 'documentation-markdown');
        }
    }
} 