<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\People;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class PeoplePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, People $people): bool
    {
        return $user->belongsToTeam($people->team);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function update(User $user, People $people): bool
    {
        return $user->belongsToTeam($people->team);
    }

    public function delete(User $user, People $people): bool
    {
        return $user->belongsToTeam($people->team);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function restore(User $user, People $people): bool
    {
        return $user->belongsToTeam($people->team);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function forceDelete(User $user, People $people): bool
    {
        return $user->hasTeamRole($people->team, 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}
