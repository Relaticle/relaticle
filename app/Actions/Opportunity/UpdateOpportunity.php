<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Models\Opportunity;
use App\Models\User;

final readonly class UpdateOpportunity
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Opportunity $opportunity, array $data): Opportunity
    {
        abort_unless($user->can('update', $opportunity), 403);

        $opportunity->update($data);

        return $opportunity->refresh();
    }
}
