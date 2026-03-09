<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialiteProvider: string
{
    case GOOGLE = 'google';
    case GITHUB = 'github';
    case OIDC = 'oidc';

    public function label(): string
    {
        return config("services.{$this->value}.display_name")
            ?? match ($this) {
                self::GOOGLE => 'Google',
                self::GITHUB => 'GitHub',
                default => strtoupper($this->value),
            };
    }

    public function icon(): string
    {
        return config("services.{$this->value}.icon")
            ?? $this->value;
    }

    public function enabled(): bool
    {
        return match ($this) {
            self::OIDC =>
                config('services.oidc.enabled', false)
                && filled(config('services.oidc.client_id'))
                && filled(config('services.oidc.client_secret'))
                && filled(config('services.oidc.base_url')),

            default =>
                filled(config("services.{$this->value}.client_id"))
                && filled(config("services.{$this->value}.client_secret")),
        };
    }

    public static function enabledProviders(): array
    {
        return array_values(
            array_filter(self::cases(), fn ($p) => $p->enabled())
        );
    }
}