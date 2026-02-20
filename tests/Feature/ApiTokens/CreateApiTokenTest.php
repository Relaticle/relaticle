<?php

declare(strict_types=1);

use App\Livewire\App\ApiTokens\CreateApiToken;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

test('api tokens can be created', function () {
    $this->actingAs($user = User::factory()->withTeam()->create());

    livewire(CreateApiToken::class)
        ->fillForm([
            'name' => 'Test Token',
            'permissions' => ['read', 'update'],
        ])
        ->call('createToken')
        ->assertHasNoFormErrors();

    expect($user->fresh()->tokens)->toHaveCount(1);
    expect($user->fresh()->tokens->first())
        ->name->toEqual('Test Token')
        ->can('read')->toBeTrue()
        ->can('delete')->toBeFalse();
})->skip(function () {
    return ! Features::hasApiFeatures();
}, 'API support is not enabled.');

test('token name is required', function () {
    $this->actingAs(User::factory()->withTeam()->create());

    livewire(CreateApiToken::class)
        ->fillForm([
            'name' => '',
            'permissions' => ['read'],
        ])
        ->call('createToken')
        ->assertHasFormErrors(['name' => 'required']);
})->skip(function () {
    return ! Features::hasApiFeatures();
}, 'API support is not enabled.');

test('token name must be unique per user', function () {
    $this->actingAs($user = User::factory()->withTeam()->create());

    $user->tokens()->create([
        'name' => 'Existing Token',
        'token' => Str::random(40),
        'abilities' => ['read'],
    ]);

    livewire(CreateApiToken::class)
        ->fillForm([
            'name' => 'Existing Token',
            'permissions' => ['read'],
        ])
        ->call('createToken')
        ->assertHasFormErrors(['name' => 'unique']);
})->skip(function () {
    return ! Features::hasApiFeatures();
}, 'API support is not enabled.');

test('permissions are required', function () {
    $this->actingAs(User::factory()->withTeam()->create());

    livewire(CreateApiToken::class)
        ->fillForm([
            'name' => 'Test Token',
            'permissions' => [],
        ])
        ->call('createToken')
        ->assertHasFormErrors(['permissions' => 'required']);
})->skip(function () {
    return ! Features::hasApiFeatures();
}, 'API support is not enabled.');

test('plain text token is shown after creation', function () {
    $this->actingAs(User::factory()->withTeam()->create());

    $component = livewire(CreateApiToken::class)
        ->fillForm([
            'name' => 'Test Token',
            'permissions' => ['read'],
        ])
        ->call('createToken');

    expect($component->get('plainTextToken'))->not->toBeNull();
})->skip(function () {
    return ! Features::hasApiFeatures();
}, 'API support is not enabled.');
