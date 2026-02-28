<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Relaticle\Workflow\WorkflowServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WorkflowServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        // Landing page for browser tests that need a page to execute JS from
        $router->get('/_test', fn () => response('<html><body>OK</body></html>'));
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('workflow.middleware', []);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
