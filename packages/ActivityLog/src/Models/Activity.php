<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Relaticle\ActivityLog\Scopes\TeamScope;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * @property string|null $team_id
 */
final class Activity extends SpatieActivity
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    protected static function booted(): void
    {
        self::addGlobalScope(new TeamScope);

        self::creating(function (Activity $activity): void {
            if (! $activity->team_id && $activity->subject) {
                $teamId = $activity->subject->getAttribute('team_id');

                if ($teamId) {
                    $activity->team_id = $teamId;
                }
            }
        });
    }
}
