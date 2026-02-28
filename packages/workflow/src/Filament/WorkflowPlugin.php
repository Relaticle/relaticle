<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class WorkflowPlugin implements Plugin
{
    public function getId(): string
    {
        return 'workflow';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\WorkflowResource::class,
            Resources\WorkflowRunResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }
}
