<?php

declare(strict_types=1);

use App\Livewire\Chat\ChatSidePanel;
use App\Models\User;

mutates(ChatSidePanel::class);

it('shows the chat side panel toggle button', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs("/app/{$team->slug}")
        ->assertPresent('[title="Open AI Chat (Cmd+J)"]');
});

it('can open the side panel by clicking the toggle button', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs("/app/{$team->slug}")
        ->click('[title="Open AI Chat (Cmd+J)"]')
        ->assertSee('AI Chat');
});
