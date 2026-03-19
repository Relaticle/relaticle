<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\URL;

test('guest clicking invitation link sees team name on login page', function () {
    $team = Team::factory()->create(['name' => 'Acme Corp']);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newuser@example.com',
    ]);

    $acceptUrl = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation]);

    // Guest hits invite link -> auth middleware redirects to login with url.intended set
    $this->get($acceptUrl)
        ->assertRedirect();

    $this->get(route('filament.app.auth.login'))
        ->assertSee('Acme Corp');
});
