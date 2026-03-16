<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\CreateTeam;
use App\Models\User;

mutates(Register::class, CreateTeam::class);

it('new user can register and complete onboarding', function (): void {
    $this->visit('/app/register')
        ->type('[id="form.name"]', 'Onboarding Tester')
        ->type('[id="form.email"]', 'onboard-browser@gmail.com')
        ->type('[id="form.password"]', 'Password123!')
        ->type('[id="form.passwordConfirmation"]', 'Password123!')
        ->press('Sign up')
        ->assertSee('Create your workspace')
        ->type('[id="form.name"]', 'My First Workspace')
        ->type('[id="form.slug"]', 'my-first-workspace')
        ->press('Create Team')
        ->assertPathContains('/app/my-first-workspace/companies');

    $user = User::where('email', 'onboard-browser@gmail.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->ownedTeams)->toHaveCount(1)
        ->and($user->ownedTeams->first()->name)->toBe('My First Workspace');
});
