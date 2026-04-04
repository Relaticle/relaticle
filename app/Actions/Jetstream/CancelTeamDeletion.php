<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamDeletionCancelledNotification;

final readonly class CancelTeamDeletion
{
    public function cancel(Team $team): void
    {
        $team->update(['scheduled_deletion_at' => null]);

        /** @var User $owner */
        $owner = $team->owner;

        $team->allUsers()
            ->reject(fn (User $member): bool => $member->id === $owner->id)
            ->each(fn (User $member) => $member->notify(new TeamDeletionCancelledNotification($team)));
    }
}
