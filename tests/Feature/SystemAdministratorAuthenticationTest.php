<?php

declare(strict_types=1);

use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;
use App\Models\SystemAdministrator;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel('sysadmin');
});

it('can create system administrator', function () {
    $admin = SystemAdministrator::factory()->create([
        'email' => 'test@example.com',
        'role' => SystemAdministratorRole::SuperAdministrator,
    ]);

    expect($admin)
        ->email->toBe('test@example.com')
        ->role->toBe(SystemAdministratorRole::SuperAdministrator)
        ->hasVerifiedEmail()->toBeTrue();
});

it('can authenticate system administrator', function () {
    $admin = SystemAdministrator::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($admin, 'sysadmin');

    $this->assertAuthenticatedAs($admin, 'sysadmin');
});

it('cannot authenticate system administrator with wrong password', function () {
    SystemAdministrator::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response = $this->post('/sysadmin/login', [
        'email' => 'admin@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest('sysadmin');
});

it('system administrator can access sysadmin panel', function () {
    $admin = SystemAdministrator::factory()->create();

    expect($admin->canAccessPanel(Filament::getPanel('sysadmin')))->toBeTrue();
});

it('system administrator cannot access app panel', function () {
    $admin = SystemAdministrator::factory()->create();

    expect($admin->canAccessPanel(Filament::getPanel('app')))->toBeFalse();
});

it('regular user cannot access sysadmin panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('sysadmin')))->toBeFalse();
});

it('system administrator and regular user sessions are isolated', function () {
    $admin = SystemAdministrator::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    // Login as system administrator
    $this->post('/sysadmin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'sysadmin');
    $this->assertGuest('web');

    // Login as regular user should not affect sysadmin auth
    $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user, 'web');
    $this->assertAuthenticatedAs($admin, 'sysadmin');
});

it('system administrator can logout without affecting user session', function () {
    $admin = SystemAdministrator::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    // Login both
    $this->post('/sysadmin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'sysadmin');
    $this->assertAuthenticatedAs($user, 'web');

    // Logout system administrator
    $this->post('/sysadmin/logout');

    $this->assertGuest('sysadmin');
    $this->assertAuthenticatedAs($user, 'web');
});

it('can access system administrator management page', function () {
    $admin = SystemAdministrator::factory()->create();

    $this->actingAs($admin, 'sysadmin')
        ->get('/sysadmin/system-administrators')
        ->assertOk();
});

it('can create new system administrator through interface', function () {
    $admin = SystemAdministrator::factory()->create();

    $this->actingAs($admin, 'sysadmin')
        ->post('/sysadmin/system-administrators', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password',
            'role' => SystemAdministratorRole::SuperAdministrator->value,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('system_administrators', [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'role' => SystemAdministratorRole::SuperAdministrator->value,
    ]);
});

it('validates system administrator creation', function () {
    $admin = SystemAdministrator::factory()->create();

    $this->actingAs($admin, 'sysadmin')
        ->post('/sysadmin/system-administrators', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '',
            'role' => 'invalid-role',
        ])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

it('prevents duplicate system administrator emails', function () {
    $existingAdmin = SystemAdministrator::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $admin = SystemAdministrator::factory()->create();

    $this->actingAs($admin, 'sysadmin')
        ->post('/sysadmin/system-administrators', [
            'name' => 'Duplicate Admin',
            'email' => 'existing@example.com',
            'password' => 'password',
            'role' => SystemAdministratorRole::SuperAdministrator->value,
        ])
        ->assertSessionHasErrors(['email']);
});

it('unverified system administrator cannot access panel', function () {
    $admin = SystemAdministrator::factory()->unverified()->create();

    expect($admin->canAccessPanel(Filament::getPanel('sysadmin')))->toBeFalse();
});