<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Observers;

use Illuminate\Database\Eloquent\Model;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Triggers\RecordEventTrigger;

class WorkflowModelObserver
{
    public function __construct(
        protected readonly RecordEventTrigger $trigger,
    ) {}

    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        $this->handleEvent($model, 'created');
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->handleEvent($model, 'updated');
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->handleEvent($model, 'deleted');
    }

    /**
     * Find matching workflows for the given event and dispatch jobs.
     */
    protected function handleEvent(Model $model, string $event): void
    {
        $workflows = $this->trigger->getMatchingWorkflows($model, $event);

        foreach ($workflows as $workflow) {
            if ($this->trigger->shouldTrigger($workflow, $model, $event)) {
                $context = $this->trigger->buildContext($model, $event);

                ExecuteWorkflowJob::dispatch($workflow, $context);
                $workflow->update(['last_triggered_at' => now()]);
            }
        }
    }
}
