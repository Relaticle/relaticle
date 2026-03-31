<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Relaticle\ActivityLog\Models\Activity;

final readonly class ActivityPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail() // @phpstan-ignore function.alreadyNarrowedType
            && method_exists($user, 'currentTeam') && $user->currentTeam !== null; // @phpstan-ignore function.alreadyNarrowedType, property.notFound
    }

    public function view(Authenticatable $user, Activity $activity): bool
    {
        if (! method_exists($user, 'currentTeam')) { // @phpstan-ignore function.alreadyNarrowedType
            return false;
        }

        return $user->currentTeam?->id === $activity->team_id; // @phpstan-ignore property.notFound
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
