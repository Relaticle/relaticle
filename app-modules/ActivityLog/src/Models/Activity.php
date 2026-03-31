<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Relaticle\ActivityLog\Scopes\TeamScope;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

final class Activity extends SpatieActivity
{
    use HasFactory;

    protected static function booted(): void
    {
        self::addGlobalScope(new TeamScope);

        self::creating(function (Activity $activity): void {
            if (! $activity->team_id && $activity->subject && method_exists($activity->subject, 'team')) {
                $activity->team_id = $activity->subject->team_id;
            }
        });
    }
}
