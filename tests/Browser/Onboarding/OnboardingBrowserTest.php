<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\CreateTeam;
use App\Models\User;

mutates(Register::class, CreateTeam::class);

it('new user can register and complete onboarding', function (): void {
    $this->visit('/app/register')
        ->type('data.name', 'Onboarding Tester')
        ->type('data.email', 'onboard-browser@example.com')
        ->type('data.password', 'Password123!')
        ->type('data.passwordConfirmation', 'Password123!')
        ->press('Sign up')
        ->assertSee('Create your workspace')
        ->type('data.name', 'My First Workspace')
        ->press('Register')
        ->assertPathContains('/app/my-first-workspace/companies');

    $user = User::where('email', 'onboard-browser@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->ownedTeams)->toHaveCount(1)
        ->and($user->ownedTeams->first()->name)->toBe('My First Workspace');
});
