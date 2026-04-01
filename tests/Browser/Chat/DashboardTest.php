<?php

declare(strict_types=1);

use App\Filament\Pages\Dashboard;
use App\Models\Company;
use App\Models\User;

mutates(Dashboard::class);

it('can load the dashboard with CRM widgets', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    Company::factory()->for($team)->count(3)->create();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs("/app/{$team->slug}")
        ->assertSee('Ask anything about your CRM')
        ->assertSee('Companies')
        ->assertSee('3');
});

it('has a hero chat input on the dashboard', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs("/app/{$team->slug}")
        ->assertPresent('input[placeholder="Ask anything..."]');
});

it('shows suggested prompts on the dashboard', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs("/app/{$team->slug}")
        ->assertSee('CRM overview');
});
