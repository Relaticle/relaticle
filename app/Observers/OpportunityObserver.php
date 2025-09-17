<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Opportunity;

final readonly class OpportunityObserver
{
    public function creating(Opportunity $opportunity): void
    {
        if (auth('web')->check()) {
            $opportunity->creator_id = auth('web')->id();
            $opportunity->team_id = auth('web')->user()->currentTeam->getKey();
        }
    }
}
