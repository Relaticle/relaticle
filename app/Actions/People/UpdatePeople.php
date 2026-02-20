<?php

declare(strict_types=1);

namespace App\Actions\People;

use App\Models\People;
use App\Models\User;

final readonly class UpdatePeople
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, People $people, array $data): People
    {
        $user->can('update', $people) || abort(403);

        $people->update($data);

        return $people->refresh();
    }
}
