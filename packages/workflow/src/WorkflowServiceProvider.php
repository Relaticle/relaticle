<?php

declare(strict_types=1);

namespace Relaticle\Workflow;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('workflow')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WorkflowManager::class);
    }
}
