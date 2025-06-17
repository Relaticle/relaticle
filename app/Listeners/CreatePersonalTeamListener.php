<?php

declare(strict_types=1);

namespace App\Listeners;

use Filament\Auth\Events\Registered;
use App\Models\Team;
use App\Models\User;

final readonly class CreatePersonalTeamListener
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->getAuthIdentifier(),
            'name' => explode(' ', (string) $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
