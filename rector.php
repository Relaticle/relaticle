<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/app-modules',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/public',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        RemoveUnusedPrivateMethodRector::class => [
            // Skip Filament importer lifecycle hooks - they're called dynamically via callHook()
            __DIR__.'/app/Filament/Imports/*',
        ],
        PrivatizeFinalClassMethodRector::class => [
            // Filament expects protected visibility for lifecycle hooks
            __DIR__.'/app/Filament/Imports/*',
        ],
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true,
    )
    ->withPhpSets();
