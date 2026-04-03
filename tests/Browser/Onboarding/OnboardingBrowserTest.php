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
        // Step 1: Select role
        ->click('input[value="founder"]')
        ->press('Next')
        ->waitForText('What will you use Relaticle for?')
        // Step 2: Select use case
        ->click('input[value="sales_pipeline"]')
        ->press('Next')
        ->waitForText('Name your workspace')
        // Step 3: Create workspace
        ->type('[id="form.name"]', 'My First Workspace')
        ->type('[id="form.slug"]', 'my-first-workspace')
        ->press('Create workspace')
        ->assertPathContains('/my-first-workspace/companies');

    $user->refresh();

    expect($user->ownedTeams)->toHaveCount(1)
        ->and($user->ownedTeams->first()->name)->toBe('My First Workspace');
});
