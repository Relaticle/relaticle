<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivityForCrm
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept($this->getActivityLogExcludedAttributes())
            ->useLogName('crm')
            ->setDescriptionForEvent(fn (string $eventName): string => $eventName);
    }

    /** @return list<string> */
    protected function getActivityLogExcludedAttributes(): array
    {
        return array_merge(
            ['id', 'team_id', 'creator_id', 'creation_source', 'created_at', 'updated_at', 'deleted_at'],
            $this->additionalActivityLogExclusions ?? [],
        );
    }
}
