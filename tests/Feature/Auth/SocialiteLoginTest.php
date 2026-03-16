<?php

declare(strict_types=1);

use App\Enums\SocialiteProvider;
use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\RedirectController;
use App\Models\User;
use App\Models\UserSocialAccount;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

mutates(CallbackController::class, RedirectController::class);

function makeSocialiteUser(string $id, string $name, string $email): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $id;
    $user->name = $name;
    $user->email = $email;

    return $user;
}

test('redirect to socialite provider', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    Socialite::fake(SocialiteProvider::GOOGLE->value);

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::GOOGLE->value]));

    $response->assertRedirect();
});

test('callback from socialite provider creates new user when user does not exist', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    Socialite::fake(
        SocialiteProvider::GOOGLE->value,
        makeSocialiteUser('123456789', 'Test User', 'test@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::GOOGLE->value, 'code' => 'test-code']));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $this->assertDatabaseHas('user_social_accounts', [
        'provider_name' => SocialiteProvider::GOOGLE->value,
        'provider_id' => '123456789',
    ]);

    $this->assertAuthenticated();

    $response->assertRedirect(url()->getAppUrl());
});

test('callback from socialite provider logs in existing user when social account exists', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $user = User::factory()->withTeam()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    UserSocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider_name' => SocialiteProvider::GOOGLE->value,
        'provider_id' => '123456789',
    ]);

    Socialite::fake(
        SocialiteProvider::GOOGLE->value,
        makeSocialiteUser('123456789', 'Existing User', 'existing@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::GOOGLE->value, 'code' => 'test-code']));

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);

    $response->assertRedirect(url()->getAppUrl());
});

test('callback from socialite provider links social account to existing user when email matches', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $user = User::factory()->withTeam()->create([
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    Socialite::fake(
        SocialiteProvider::GOOGLE->value,
        makeSocialiteUser('123456789', 'Existing User', 'existing@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::GOOGLE->value, 'code' => 'test-code']));

    $response->assertRedirect();

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
});

test('callback from socialite provider handles error gracefully', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    Socialite::fake(
        SocialiteProvider::GOOGLE->value,
        fn () => throw new Exception('Socialite error'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::GOOGLE->value, 'code' => 'test-code']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['login']);
});

test('callback from socialite provider handles missing code parameter', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::GOOGLE->value]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['login']);
    $response->assertSessionHas('errors');

    $errors = session('errors')->getBag('default');
    expect($errors->first('login'))->toBe('Authorization was cancelled or failed. Please try again.');
});

test('provider enabled() returns false when credentials are missing', function () {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', null);
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::KEYCLOAK->enabled())->toBeFalse();
});

test('provider enabled() returns false when explicitly disabled', function () {
    config()->set('services.google.enabled', false);
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    expect(SocialiteProvider::GOOGLE->enabled())->toBeFalse();
});

test('provider enabled() returns true when configured and enabled', function () {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::KEYCLOAK->enabled())->toBeTrue();
});

test('provider enabled() defaults to true for Google and GitHub', function () {
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    expect(SocialiteProvider::GOOGLE->enabled())->toBeTrue();
});

test('provider enabled() defaults to false for SSO providers', function () {
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::KEYCLOAK->enabled())->toBeFalse();
});

test('enabledProviders() returns only enabled providers', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    config()->set('services.github.enabled', false);

    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    $providers = SocialiteProvider::enabledProviders();

    expect($providers)->toContain(SocialiteProvider::GOOGLE)
        ->toContain(SocialiteProvider::KEYCLOAK)
        ->not->toContain(SocialiteProvider::GITHUB);
});

test('redirect returns 404 for disabled provider', function () {
    config()->set('services.google.enabled', false);

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::GOOGLE->value]));

    $response->assertNotFound();
});

test('callback returns 404 for disabled provider', function () {
    config()->set('services.google.enabled', false);

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::GOOGLE->value, 'code' => 'test-code']));

    $response->assertNotFound();
});

test('redirect to keycloak provider when enabled', function () {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'test-id');
    config()->set('services.keycloak.client_secret', 'test-secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');
    config()->set('services.keycloak.realms', 'master');
    config()->set('services.keycloak.redirect', '/auth/callback/keycloak');

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::KEYCLOAK->value]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('keycloak.example.com');
});

test('callback from keycloak provider creates new user', function () {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'test-id');
    config()->set('services.keycloak.client_secret', 'test-secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    Socialite::fake(
        SocialiteProvider::KEYCLOAK->value,
        makeSocialiteUser('kc-user-123', 'Keycloak User', 'keycloak@example.com'),
    );

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::KEYCLOAK->value, 'code' => 'test-code']));

    $this->assertDatabaseHas('users', [
        'email' => 'keycloak@example.com',
        'name' => 'Keycloak User',
    ]);

    $this->assertDatabaseHas('user_social_accounts', [
        'provider_name' => SocialiteProvider::KEYCLOAK->value,
        'provider_id' => 'kc-user-123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(url()->getAppUrl());
});
