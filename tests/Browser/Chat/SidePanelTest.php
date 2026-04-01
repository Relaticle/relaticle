<?php

declare(strict_types=1);

use App\Livewire\App\Chat\ChatSidePanel;
use App\Models\User;

mutates(ChatSidePanel::class);

it('renders the side panel toggle button after login', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->visit("/app/{$team->slug}")
        ->assertSee('Dashboard');
});
