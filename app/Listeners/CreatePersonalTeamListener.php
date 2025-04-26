<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Team;
use Filament\Events\Auth\Registered;

final class CreatePersonalTeamListener
{
    public function handle(Registered $event): void
    {
        $user = $event->getUser();

        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
