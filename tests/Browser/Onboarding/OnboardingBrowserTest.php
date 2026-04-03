<?php

declare(strict_types=1);

use App\Filament\Pages\CreateTeam;
use App\Models\User;

mutates(CreateTeam::class);

it('new user without teams is directed to onboarding wizard', function (): void {
    $user = User::factory()->create();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs('/app/new')
        ->navigate('/app/new')
        ->assertSee('Welcome to Relaticle')
        // Step 1: Select use case
        ->click('[for$="onboarding_use_case-sales"]')
        ->press('Next')
        ->waitForText('How did you hear about us?')
        // Step 2: Attribution (optional, just proceed via Next wrapper)
        ->click('[x-on\\:click="requestNextStep()"]')
        ->waitForText('Name your workspace')
        // Step 3: Create workspace
        ->type('[id="form.name"]', 'My First Workspace')
        ->type('[id="form.slug"]', 'my-first-workspace')
        ->press('Create workspace')
        ->assertPathContains('/my-first-workspace/onboarding/invite');

    $user->refresh();

    expect($user->ownedTeams)->toHaveCount(1)
        ->and($user->ownedTeams->first()->name)->toBe('My First Workspace');
});
