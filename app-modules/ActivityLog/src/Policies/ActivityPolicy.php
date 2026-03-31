<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Policies;

use App\Models\User;
use Relaticle\ActivityLog\Models\Activity;

final readonly class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->currentTeam?->id === $activity->team_id;
    }

    public function create(): bool
    {
        return false;
    }

    public function update(): bool
    {
        return false;
    }

    public function delete(): bool
    {
        return false;
    }

    public function deleteAny(): bool
    {
        return false;
    }

    public function restore(): bool
    {
        return false;
    }

    public function restoreAny(): bool
    {
        return false;
    }

    public function forceDelete(): bool
    {
        return false;
    }

    public function forceDeleteAny(): bool
    {
        return false;
    }
}
