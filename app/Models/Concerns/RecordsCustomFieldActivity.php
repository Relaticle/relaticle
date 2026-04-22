<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ActivityLog\Activity;

trait RecordsCustomFieldActivity
{
    /**
     * Record custom field changes by amending the latest activity (if within 5s)
     * or creating a new `custom_field_changes` activity. Called by the
     * UsesCustomFields trait after custom field values are persisted.
     *
     * @param  list<array{code: string, label: string, type: string, old: array{value: mixed, label: mixed}, new: array{value: mixed, label: mixed}}>  $changes
     */
    public function recordCustomFieldChanges(array $changes): void
    {
        $recent = Activity::query()
            ->withoutGlobalScopes()
            ->where('subject_type', $this->getMorphClass())
            ->where('subject_id', $this->getKey())
            ->where('log_name', config('activitylog.default_log_name'))
            ->latest('id')
            ->first();

        if ($recent && $recent->created_at->diffInSeconds(now()) < 5) {
            $properties = $recent->properties?->toArray() ?? [];
            $properties['custom_field_changes'] = $changes;
            $recent->properties = collect($properties);
            $recent->save();

            return;
        }

        activity(config('activitylog.default_log_name'))
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['custom_field_changes' => $changes])
            ->event('custom_field_changes')
            ->log('custom_field_changes');
    }
}
