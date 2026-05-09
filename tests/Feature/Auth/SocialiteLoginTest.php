<?php

declare(strict_types=1);

use App\Enums\SocialiteProvider;
use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\RedirectController;
use App\Models\User;
use App\Models\UserSocialAccount;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

mutates(CallbackController::class, RedirectController::class, SocialiteProvider::class);

/**
 * @param  array<string, mixed>  $raw
 */
function makeSocialiteUser(string $id, string $name, string $email, array $raw = []): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $id;
    $user->name = $name;
    $user->email = $email;
    $user->setRaw($raw);

    return $user;
}

/**
 * @return array<string, array{0: SocialiteProvider, 1: array<string, mixed>}>
 */
function ssoProviderConfigs(): array
{
    return [
        'keycloak' => [SocialiteProvider::Keycloak, [
            'client_id' => 'id', 'client_secret' => 'secret', 'base_url' => 'https://idp.example.com',
        ]],
        'okta' => [SocialiteProvider::Okta, [
            'client_id' => 'id', 'client_secret' => 'secret', 'base_url' => 'https://idp.example.com',
        ]],
        'azure' => [SocialiteProvider::Azure, [
            'client_id' => 'id', 'client_secret' => 'secret', 'tenant' => 'common',
        ]],
        'authentik' => [SocialiteProvider::Authentik, [
            'client_id' => 'id', 'client_secret' => 'secret', 'base_url' => 'https://idp.example.com',
        ]],
        'auth0' => [SocialiteProvider::Auth0, [
            'client_id' => 'id', 'client_secret' => 'secret', 'base_url' => 'https://idp.example.com',
        ]],
    ];
}

test('redirect to socialite provider', function (): void {
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    Socialite::fake(SocialiteProvider::Google->value);

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::Google->value]));

    $response->assertRedirect();
});

test('callback from socialite provider creates new user when user does not exist', function (): void {
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    Socialite::fake(
        SocialiteProvider::Google->value,
        makeSocialiteUser('123456789', 'Test User', 'test@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $this->assertDatabaseHas('user_social_accounts', [
        'provider_name' => SocialiteProvider::Google->value,
        'provider_id' => '123456789',
    ]);

    $this->assertAuthenticated();

    $response->assertRedirect(url()->getAppUrl());
});

test('callback from socialite provider logs in existing user when social account exists', function (): void {
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $user = User::factory()->withTeam()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    UserSocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider_name' => SocialiteProvider::Google->value,
        'provider_id' => '123456789',
    ]);

    Socialite::fake(
        SocialiteProvider::Google->value,
        makeSocialiteUser('123456789', 'Existing User', 'existing@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);

    $response->assertRedirect(url()->getAppUrl());
});

test('callback from socialite provider links social account to existing user when email matches', function (): void {
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $user = User::factory()->withTeam()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    Socialite::fake(
        SocialiteProvider::Google->value,
        makeSocialiteUser('123456789', 'Existing User', 'existing@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    $response->assertRedirect();

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
});

test('callback from socialite provider handles error gracefully', function (): void {
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    Socialite::fake(
        SocialiteProvider::Google->value,
        fn () => throw new Exception('Socialite error'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['login']);
});

test('callback from socialite provider handles missing code parameter', function (): void {
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['login']);
    $response->assertSessionHas('errors');

    $errors = session('errors')->getBag('default');
    expect($errors->first('login'))->toBe('Authorization was cancelled or failed. Please try again.');
});

test('provider enabled() returns false when credentials are missing', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::Keycloak->enabled())->toBeFalse();
});

test('provider enabled() returns false when explicitly disabled', function (): void {
    config()->set('services.google.enabled', false);
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    expect(SocialiteProvider::Google->enabled())->toBeFalse();
});

test('provider enabled() returns true when configured and enabled', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::Keycloak->enabled())->toBeTrue();
});

test('provider enabled() defaults to true for Google and GitHub', function (): void {
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    expect(SocialiteProvider::Google->enabled())->toBeTrue();
});

test('provider enabled() defaults to false for SSO providers', function (): void {
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::Keycloak->enabled())->toBeFalse();
});

test('enabledProviders() returns only enabled providers', function (): void {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    config()->set('services.github.enabled', false);

    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    $providers = SocialiteProvider::enabledProviders();

    expect($providers)->toContain(SocialiteProvider::Google)
        ->toContain(SocialiteProvider::Keycloak)
        ->not->toContain(SocialiteProvider::GitHub);
});

test('redirect returns 404 for disabled provider', function (): void {
    config()->set('services.google.enabled', false);

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::Google->value]));

    $response->assertNotFound();
});

test('callback returns 404 for disabled provider', function (): void {
    config()->set('services.google.enabled', false);

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    $response->assertNotFound();
});

test('redirect to keycloak provider when enabled', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'test-id');
    config()->set('services.keycloak.client_secret', 'test-secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');
    config()->set('services.keycloak.realms', 'master');
    config()->set('services.keycloak.redirect', '/auth/callback/keycloak');

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::Keycloak->value]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('keycloak.example.com');
});

test('callback from keycloak provider creates new user', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'test-id');
    config()->set('services.keycloak.client_secret', 'test-secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    Socialite::fake(
        SocialiteProvider::Keycloak->value,
        makeSocialiteUser('kc-user-123', 'Keycloak User', 'keycloak@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Keycloak->value, 'code' => 'test-code']));

    $this->assertDatabaseHas('users', [
        'email' => 'keycloak@example.com',
        'name' => 'Keycloak User',
    ]);

    $this->assertDatabaseHas('user_social_accounts', [
        'provider_name' => SocialiteProvider::Keycloak->value,
        'provider_id' => 'kc-user-123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(url()->getAppUrl());
});

test('redirect and callback work for every SSO provider when configured', function (SocialiteProvider $provider, array $config): void {
    foreach ($config as $key => $value) {
        config()->set("services.{$provider->value}.{$key}", $value);
    }
    config()->set("services.{$provider->value}.enabled", true);

    $providerId = "{$provider->value}-id";
    $email = "{$provider->value}@example.com";

    Socialite::fake(
        $provider->value,
        makeSocialiteUser($providerId, 'SSO User', $email),
    );

    $callback = $this->get(route('auth.socialite.callback', ['provider' => $provider->value, 'code' => 'test-code']));

    $this->assertDatabaseHas('user_social_accounts', [
        'provider_name' => $provider->value,
        'provider_id' => $providerId,
    ]);

    $this->assertAuthenticated();
    $callback->assertRedirect(url()->getAppUrl());
})->with(ssoProviderConfigs());

test('redirect and callback return 404 for every SSO provider when disabled', function (SocialiteProvider $provider): void {
    config()->set("services.{$provider->value}.enabled", false);

    $this->get(route('auth.socialite.redirect', ['provider' => $provider->value]))->assertNotFound();
    $this->get(route('auth.socialite.callback', ['provider' => $provider->value, 'code' => 'x']))->assertNotFound();
})->with([
    'keycloak' => [SocialiteProvider::Keycloak],
    'okta' => [SocialiteProvider::Okta],
    'azure' => [SocialiteProvider::Azure],
    'authentik' => [SocialiteProvider::Authentik],
    'auth0' => [SocialiteProvider::Auth0],
]);

test('SSO provider blocks email-link when email_verified claim is missing', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://idp.example.com');

    User::factory()->withTeam()->create(['email' => 'victim@example.com']);

    Socialite::fake(
        SocialiteProvider::Keycloak->value,
        makeSocialiteUser('attacker-1', 'Attacker', 'victim@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', [
        'provider' => SocialiteProvider::Keycloak->value,
        'code' => 'test-code',
    ]));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['login']);

    $this->assertDatabaseMissing('user_social_accounts', [
        'provider_name' => SocialiteProvider::Keycloak->value,
        'provider_id' => 'attacker-1',
    ]);
});

test('SSO provider blocks email-link when email_verified claim is false', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://idp.example.com');

    User::factory()->withTeam()->create(['email' => 'victim@example.com']);

    Socialite::fake(
        SocialiteProvider::Keycloak->value,
        makeSocialiteUser('attacker-2', 'Attacker', 'victim@example.com', ['email_verified' => false]),
    );

    $response = $this->get(route('auth.socialite.callback', [
        'provider' => SocialiteProvider::Keycloak->value,
        'code' => 'test-code',
    ]));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('SSO provider links to existing user when email_verified claim is true', function (): void {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://idp.example.com');

    $user = User::factory()->withTeam()->create(['email' => 'employee@example.com']);

    Socialite::fake(
        SocialiteProvider::Keycloak->value,
        makeSocialiteUser('kc-employee-1', 'Employee', 'employee@example.com', ['email_verified' => true]),
    );

    $response = $this->get(route('auth.socialite.callback', [
        'provider' => SocialiteProvider::Keycloak->value,
        'code' => 'test-code',
    ]));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(url()->getAppUrl());

    $this->assertDatabaseHas('user_social_accounts', [
        'user_id' => $user->id,
        'provider_name' => SocialiteProvider::Keycloak->value,
        'provider_id' => 'kc-employee-1',
    ]);
});
