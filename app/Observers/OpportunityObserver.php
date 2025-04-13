<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Opportunity;

final readonly class OpportunityObserver
{
    public function creating(Opportunity $opportunity): void
    {
        if (auth()->check()) {
            $opportunity->creator_id = auth()->id();
            $opportunity->team_id = auth()->user()->currentTeam->getKey();
        }
    }
}
