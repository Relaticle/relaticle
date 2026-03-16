<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialiteProvider: string
{
    case Google = 'google';
    case GitHub = 'github';
    case Keycloak = 'keycloak';
    case Okta = 'okta';
    case Azure = 'azure';
    case Authentik = 'authentik';
    case Auth0 = 'auth0';

    private const array SSO_PROVIDERS = [
        self::Keycloak->value,
        self::Okta->value,
        self::Azure->value,
        self::Authentik->value,
        self::Auth0->value,
    ];

    public function enabled(): bool
    {
        $key = $this->value;
        $defaultEnabled = ! in_array($key, self::SSO_PROVIDERS, true);

        if (! (bool) config("services.{$key}.enabled", $defaultEnabled)) {
            return false;
        }

        $required = match ($this) {
            self::Google, self::GitHub, self::Azure => ['client_id', 'client_secret'],
            default => ['client_id', 'client_secret', 'base_url'],
        };

        foreach ($required as $field) {
            if (! filled(config("services.{$key}.{$field}"))) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, self> */
    public static function enabledProviders(): array
    {
        return array_values(array_filter(self::cases(), fn (self $provider): bool => $provider->enabled()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::GitHub => 'GitHub',
            self::Keycloak => (string) config('services.keycloak.display_name', 'Keycloak'),
            self::Okta => 'Okta',
            self::Azure => 'Microsoft',
            self::Authentik => 'Authentik',
            self::Auth0 => 'Auth0',
        };
    }
}
