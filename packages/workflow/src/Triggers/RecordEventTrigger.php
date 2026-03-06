<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers;

use Illuminate\Database\Eloquent\Model;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\Contracts\WorkflowTrigger;

class RecordEventTrigger implements WorkflowTrigger
{
    public static function type(): TriggerType
    {
        return TriggerType::RecordEvent;
    }

    /**
     * Find all active workflows that should be triggered for the given model and event.
     *
     * @param  Model  $model  The Eloquent model that triggered the event
     * @param  string  $event  The event name (created, updated, deleted)
     * @return \Illuminate\Database\Eloquent\Collection<int, Workflow>
     */
    public function getMatchingWorkflows(Model $model, string $event): \Illuminate\Database\Eloquent\Collection
    {
        $modelClass = get_class($model);

        return Workflow::query()
            ->where('status', WorkflowStatus::Live)
            ->where('trigger_type', TriggerType::RecordEvent)
            ->where('trigger_config->model', $modelClass)
            ->where('trigger_config->event', $event)
            ->get();
    }

    /**
     * Determine whether a workflow should fire for the given model event.
     *
     * For "updated" events with a field/value filter, checks that:
     *  1. The specified field was actually changed
     *  2. The new value matches the target value (if specified)
     *  3. The old value matches the from_value (if specified)
     *
     * @param  Workflow  $workflow  The workflow to evaluate
     * @param  Model  $model  The Eloquent model that triggered the event
     * @param  string  $event  The event name
     * @return bool
     */
    public function shouldTrigger(Workflow $workflow, Model $model, string $event): bool
    {
        $config = $workflow->trigger_config ?? [];

        // For updated events with field-level filtering
        if ($event === 'updated' && isset($config['field'])) {
            $field = $config['field'];

            // The field must have actually changed
            if (! $model->wasChanged($field)) {
                return false;
            }

            // If a "from" value is specified, the original value must match
            if (isset($config['from_value']) && $config['from_value'] !== '') {
                $originalValue = (string) $model->getOriginal($field);
                if ($originalValue !== (string) $config['from_value']) {
                    return false;
                }
            }

            // If a "to" value is specified, the new value must match
            if (isset($config['value']) && $config['value'] !== '') {
                return (string) $model->getAttribute($field) === (string) $config['value'];
            }
        }

        return true;
    }

    /**
     * Build the context data array for the dispatched workflow job.
     *
     * @param  Model  $model  The Eloquent model
     * @param  string  $event  The event name
     * @return array<string, mixed>
     */
    public function buildContext(Model $model, string $event): array
    {
        $context = [
            'record' => $model->toArray(),
            'event' => $event,
            'model_class' => get_class($model),
            'model_id' => $model->getKey(),
        ];

        // For update events, include changed fields info
        if ($event === 'updated') {
            $changes = $model->getChanges();
            $changedFields = [];

            foreach ($changes as $field => $newValue) {
                if ($field === 'updated_at') {
                    continue;
                }
                $changedFields[$field] = [
                    'from' => $model->getOriginal($field),
                    'to' => $newValue,
                ];
            }

            $context['changes'] = $changedFields;
        }

        return $context;
    }
}
