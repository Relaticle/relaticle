<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\People;

final class PeopleObserver
{
    public function creating(People $people): void
    {
        if (auth()->check()) {
            $people->creator_id = auth()->id();
            $people->team_id = auth()->user()->currentTeam->getKey();
        }
    }
}
