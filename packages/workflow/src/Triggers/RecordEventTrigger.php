<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers;

use Illuminate\Database\Eloquent\Model;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

class RecordEventTrigger
{
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
            ->where('is_active', true)
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
     *  2. The new value matches the target value
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

            // If a target value is specified, the new value must match
            if (isset($config['value'])) {
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
        return [
            'record' => $model->toArray(),
            'event' => $event,
            'model_class' => get_class($model),
            'model_id' => $model->getKey(),
        ];
    }
}
