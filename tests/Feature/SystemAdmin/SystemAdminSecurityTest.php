<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;
use Relaticle\SystemAdmin\Models\SystemAdministrator;

describe('SystemAdmin Security', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('sysadmin');
    });

    it('enforces complete authentication isolation', function () {
        $admin = SystemAdministrator::factory()->create();
        $user = User::factory()->create();

        // System admin cannot access app panel
        expect($admin->canAccessPanel(Filament::getPanel('app')))->toBeFalse();

        // Regular user cannot access sysadmin panel
        expect($user->canAccessPanel(Filament::getPanel('sysadmin')))->toBeFalse();

        // Guards are isolated
        $this->actingAs($admin, 'sysadmin');
        $this->assertAuthenticatedAs($admin, 'sysadmin');
        $this->assertGuest('web');
    });

    it('enforces role-based authorization', function () {
        $superAdmin = SystemAdministrator::factory()->create([
            'role' => SystemAdministratorRole::SuperAdministrator,
        ]);

        $otherAdmin = SystemAdministrator::factory()->create([
            'role' => SystemAdministratorRole::SuperAdministrator,
        ]);

        $this->actingAs($superAdmin, 'sysadmin');

        expect(auth('sysadmin')->user()->can('create', SystemAdministrator::class))->toBeTrue()
            ->and(auth('sysadmin')->user()->can('viewAny', SystemAdministrator::class))->toBeTrue()
            ->and(auth('sysadmin')->user()->can('update', $otherAdmin))->toBeTrue()
            ->and(auth('sysadmin')->user()->can('delete', $otherAdmin))->toBeTrue()
            ->and(auth('sysadmin')->user()->can('delete', $superAdmin))->toBeFalse();
    });

    it('requires email verification for panel access', function () {
        $unverifiedAdmin = SystemAdministrator::factory()->unverified()->create();

        expect($unverifiedAdmin->canAccessPanel(Filament::getPanel('sysadmin')))->toBeFalse();
    });

    it('protects routes with authentication', function () {
        $this->get('/sysadmin/system-administrators')
            ->assertRedirect('/sysadmin/login');

        $admin = SystemAdministrator::factory()->create();

        $this->actingAs($admin, 'sysadmin')
            ->get('/sysadmin/system-administrators')
            ->assertOk();
    });

    it('validates data integrity', function () {
        $admin = SystemAdministrator::factory()->create([
            'role' => SystemAdministratorRole::SuperAdministrator,
        ]);

        expect($admin->role)->toBeInstanceOf(SystemAdministratorRole::class)
            ->and($admin->role)->toBe(SystemAdministratorRole::SuperAdministrator)
            ->and($admin->hasVerifiedEmail())->toBeTrue();
    });
});
