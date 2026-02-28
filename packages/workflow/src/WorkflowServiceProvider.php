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
            ->hasViews('workflow')
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WorkflowManager::class, function () {
            $manager = new WorkflowManager();
            $manager->registerAction('delay', \Relaticle\Workflow\Actions\DelayAction::class);
            $manager->registerAction('loop', \Relaticle\Workflow\Actions\LoopAction::class);
            $manager->registerAction('send_webhook', \Relaticle\Workflow\Actions\SendWebhookAction::class);
            $manager->registerAction('send_email', \Relaticle\Workflow\Actions\SendEmailAction::class);
            $manager->registerAction('http_request', \Relaticle\Workflow\Actions\HttpRequestAction::class);

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

        // Conditionally load Filament views/assets if Filament is installed
        if (class_exists(\Filament\Panel::class)) {
            // Filament is available — assets and views will be loaded by the plugin
        }
    }
}
