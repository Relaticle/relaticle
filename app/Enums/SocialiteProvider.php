<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialiteProvider: string
{
    case GOOGLE = 'google';
    case GITHUB = 'github';
    case KEYCLOAK = 'keycloak';
    case OKTA = 'okta';
    case AZURE = 'azure';
    case AUTHENTIK = 'authentik';
    case AUTH0 = 'auth0';

    private const array SSO_PROVIDERS = [
        self::KEYCLOAK->value,
        self::OKTA->value,
        self::AZURE->value,
        self::AUTHENTIK->value,
        self::AUTH0->value,
    ];

    public function enabled(): bool
    {
        $key = $this->value;
        $defaultEnabled = ! in_array($key, self::SSO_PROVIDERS, true);

        if (! (bool) config("services.{$key}.enabled", $defaultEnabled)) {
            return false;
        }

        $required = match ($this) {
            self::GOOGLE, self::GITHUB, self::AZURE => ['client_id', 'client_secret'],
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
            self::GOOGLE => 'Google',
            self::GITHUB => 'GitHub',
            self::KEYCLOAK => (string) config('services.keycloak.display_name', 'Keycloak'),
            self::OKTA => 'Okta',
            self::AZURE => 'Microsoft',
            self::AUTHENTIK => 'Authentik',
            self::AUTH0 => 'Auth0',
        };
    }
}
