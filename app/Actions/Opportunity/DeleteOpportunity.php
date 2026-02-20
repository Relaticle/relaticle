<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Models\Opportunity;
use App\Models\User;

final readonly class DeleteOpportunity
{
    public function execute(User $user, Opportunity $opportunity): void
    {
        $user->can('delete', $opportunity) || abort(403);

        $opportunity->delete();
    }
}
