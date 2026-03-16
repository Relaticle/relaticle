<?php

declare(strict_types=1);

use App\Enums\SocialiteProvider;
use App\Models\User;
use App\Models\UserSocialAccount;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

test('redirect to socialite provider', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::Google->value]));

    $response->assertRedirect();
});

test('callback from socialite provider creates new user when user does not exist', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('Test User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with(SocialiteProvider::Google->value)
        ->andReturn($provider);

    // Make the request to the callback route with required code parameter
    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    // Assert that a new user was created
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    // Assert that a social account was created
    $this->assertDatabaseHas('user_social_accounts', [
        'provider_name' => SocialiteProvider::Google->value,
        'provider_id' => '123456789',
    ]);

    // Assert that the user is authenticated
    $this->assertAuthenticated();

    // Assert that the user is redirected to the dashboard
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
        'provider_name' => SocialiteProvider::Google->value,
        'provider_id' => '123456789',
    ]);

    // Mock the Socialite facade
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with(SocialiteProvider::Google->value)
        ->andReturn($provider);

    // Make the request to the callback route with required code parameter
    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    // Assert that the user is authenticated
    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);

    // Assert that the user is redirected to the app URL
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

    // Mock the Socialite facade
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with(SocialiteProvider::Google->value)
        ->andReturn($provider);

    // Make the request to the callback route with required code parameter
    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    // Check if the response is a redirect to the dashboard
    $response->assertRedirect();

    // Assert that the user is authenticated
    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
});

test('callback from socialite provider handles error gracefully', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andThrow(new Exception('Socialite error'));

    Socialite::shouldReceive('driver')
        ->with(SocialiteProvider::Google->value)
        ->andReturn($provider);

    // Make the request to the callback route with required code parameter
    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    // Assert that the user is redirected to the login page with an error
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['login']);
});

test('callback from socialite provider handles missing code parameter', function () {
    config()->set('services.google.enabled', true);
    config()->set('services.google.client_id', 'test-id');
    config()->set('services.google.client_secret', 'test-secret');

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value]));

    // Assert that the user is redirected to the login page with an error
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

    expect(SocialiteProvider::Keycloak->enabled())->toBeFalse();
});

test('provider enabled() returns false when explicitly disabled', function () {
    config()->set('services.google.enabled', false);
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    expect(SocialiteProvider::Google->enabled())->toBeFalse();
});

test('provider enabled() returns true when configured and enabled', function () {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::Keycloak->enabled())->toBeTrue();
});

test('provider enabled() defaults to true for Google and GitHub', function () {
    config()->set('services.google.client_id', 'id');
    config()->set('services.google.client_secret', 'secret');

    expect(SocialiteProvider::Google->enabled())->toBeTrue();
});

test('provider enabled() defaults to false for SSO providers', function () {
    config()->set('services.keycloak.client_id', 'id');
    config()->set('services.keycloak.client_secret', 'secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    expect(SocialiteProvider::Keycloak->enabled())->toBeFalse();
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

    expect($providers)->toContain(SocialiteProvider::Google)
        ->toContain(SocialiteProvider::Keycloak)
        ->not->toContain(SocialiteProvider::GitHub);
});

test('redirect returns 404 for disabled provider', function () {
    config()->set('services.google.enabled', false);

    $response = $this->get(route('auth.socialite.redirect', ['provider' => SocialiteProvider::Google->value]));

    $response->assertNotFound();
});

test('callback returns 404 for disabled provider', function () {
    config()->set('services.google.enabled', false);

    $response = $this->get(route('auth.socialite.callback', ['provider' => SocialiteProvider::Google->value, 'code' => 'test-code']));

    $response->assertNotFound();
});

test('redirect to keycloak provider when enabled', function () {
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

test('callback from keycloak provider creates new user', function () {
    config()->set('services.keycloak.enabled', true);
    config()->set('services.keycloak.client_id', 'test-id');
    config()->set('services.keycloak.client_secret', 'test-secret');
    config()->set('services.keycloak.base_url', 'https://keycloak.example.com');

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('kc-user-123');
    $socialiteUser->shouldReceive('getName')->andReturn('Keycloak User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('keycloak@example.com');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with(SocialiteProvider::Keycloak->value)
        ->andReturn($provider);

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
