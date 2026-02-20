<?php

declare(strict_types=1);

namespace App\Actions\People;

use App\Models\People;
use App\Models\User;

final readonly class DeletePeople
{
    public function execute(User $user, People $people): void
    {
        $user->can('delete', $people) || abort(403);

        $people->delete();
    }
}
