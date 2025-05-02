<?php

declare(strict_types=1);

namespace Relaticle\Documentation;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class DocumentationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerComponents();
    }

    /**
     * Register the module routes.
     */
    private function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(function (): void {
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
     * Register Blade components.
     */
    private function registerComponents(): void
    {
        // Register components with the 'documentation::' namespace
        Blade::componentNamespace('Relaticle\\Documentation\\Components', 'documentation');

        // Register anonymous components
        $this->loadViewComponentsAs('documentation', []);
    }
}
