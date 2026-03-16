<?php

declare(strict_types=1);

use App\Filament\Pages\CreateTeam;
use App\Models\Team;
use App\Models\User;

mutates(CreateTeam::class);

it('can create a new team through the browser', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="data.email"]', $user->email)
        ->type('[id="data.password"]', 'password')
        ->press('Sign in')
        ->assertPathIs("/app/{$team->slug}/companies")
        ->navigate('/app/new')
        ->assertSee('Create your workspace')
        ->type('[id="data.name"]', 'Second Workspace')
        ->press('Register')
        ->assertPathContains('/app/second-workspace/companies');

    expect(Team::where('name', 'Second Workspace')->where('user_id', $user->id)->exists())->toBeTrue();
});
