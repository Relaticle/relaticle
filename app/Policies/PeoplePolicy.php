<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\People;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class PeoplePolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public function restore(): bool
    {
        return true;
    }

    public function forceDelete(User $user, People $people): bool
    {
        return $user->hasTeamRole($people->team, 'admin');
    }
}
