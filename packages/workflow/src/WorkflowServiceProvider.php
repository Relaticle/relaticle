<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

use Relaticle\Workflow\Engine\ConditionEvaluator;
use Relaticle\Workflow\Engine\VariableResolver;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('workflow')
            ->hasConfigFile()
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WorkflowManager::class, function () {
            $manager = new WorkflowManager();
            $manager->registerAction('delay', \Relaticle\Workflow\Actions\DelayAction::class);

            return $manager;
        });

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
    }
}
