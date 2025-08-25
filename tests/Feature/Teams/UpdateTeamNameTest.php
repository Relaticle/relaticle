<?php

declare(strict_types=1);

use App\Livewire\App\Teams\UpdateTeamName;
use App\Models\User;
use Livewire\Livewire;

test('team names can be updated', function () {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test(UpdateTeamName::class, ['team' => $user->currentTeam])
        ->fillForm(['name' => 'Test Team'])
        ->call('updateTeamName', $user->currentTeam)
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->currentTeam->fresh()->name)->toEqual('Test Team');
});
