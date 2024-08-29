<?php

namespace ManukMinasyan\FilamentAttribute;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource;

class FilamentAttributePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-attribute';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AttributeResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
