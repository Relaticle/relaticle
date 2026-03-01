<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

use Livewire\Livewire;
use Relaticle\Workflow\Engine\ConditionEvaluator;
use Relaticle\Workflow\Engine\VariableResolver;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Livewire\WorkflowConfigPanel;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('workflow')
            ->hasConfigFile()
            ->hasViews('workflow')
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RelaticleSchema::class);

        $this->app->singleton(WorkflowManager::class);

        $this->app->bind(WorkflowExecutor::class, function ($app) {
            return new WorkflowExecutor(
                $app->make(WorkflowManager::class),
                new ConditionEvaluator(),
                new VariableResolver(),
            );
        });
    }

    public function packageBooted(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Register Livewire components
        if (class_exists(Livewire::class) && $this->app->bound('livewire')) {
            Livewire::component('workflow-config-panel', WorkflowConfigPanel::class);
        }
    }
}
