<?php

declare(strict_types=1);

use App\Filament\Pages\Chat;
use App\Models\User;

mutates(Chat::class);

it('can load the chat page', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->visit("/app/{$team->slug}/chat")
        ->assertSee('AI Chat');
});

it('has a message input field on the chat page', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->visit("/app/{$team->slug}/chat")
        ->assertSee('AI Chat');
});
