<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Concerns;

use Relaticle\ActivityLog\Models\Activity;
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
        $base = ['id', 'team_id', 'creator_id', 'creation_source', 'custom_fields', 'created_at', 'updated_at', 'deleted_at'];

        /** @var list<string> $additional */
        $additional = $this->additionalActivityLogExclusions ?? []; // @phpstan-ignore nullCoalesce.property

        return [...$base, ...$additional];
    }

    /**
     * Record custom field changes by amending the latest activity or creating a new one.
     *
     * Called by the UsesCustomFields trait after custom field values are persisted.
     *
     * @param  list<array{code: string, label: string, type: string, old: array{value: mixed, label: mixed}, new: array{value: mixed, label: mixed}}>  $changes
     */
    public function recordCustomFieldChanges(array $changes): void
    {
        $query = Activity::query()
            ->withoutGlobalScopes()
            ->where('subject_type', $this->getMorphClass())
            ->where('subject_id', $this->getKey())
            ->where('log_name', 'crm');

        if ($teamId = $this->getAttribute('team_id')) {
            $query->where('team_id', $teamId);
        }

        $activity = $query->latest('id')->first();

        if ($activity && $activity->created_at->diffInSeconds(now()) < 5) {
            $properties = $activity->properties->toArray();
            $properties['custom_field_changes'] = $changes;
            $activity->properties = $properties;
            $activity->save();

            return;
        }

        activity('crm')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['custom_field_changes' => $changes])
            ->event('updated')
            ->log('updated');
    }
}
