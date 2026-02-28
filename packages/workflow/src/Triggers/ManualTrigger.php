<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers;

use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\Contracts\WorkflowTrigger;

class ManualTrigger implements WorkflowTrigger
{
    public static function type(): TriggerType
    {
        return TriggerType::Manual;
    }

    /**
     * Trigger a manual workflow, dispatching the execution job.
     *
     * @param  Workflow  $workflow  The workflow to trigger
     * @param  array<string, mixed>  $context  Optional context data (e.g. record information)
     *
     * @throws \InvalidArgumentException If the workflow is inactive or not a manual trigger type
     */
    public function trigger(Workflow $workflow, array $context = []): void
    {
        if (! $workflow->is_active) {
            throw new \InvalidArgumentException('Workflow is not active.');
        }

        if ($workflow->trigger_type !== TriggerType::Manual) {
            throw new \InvalidArgumentException('Workflow is not a manual trigger type.');
        }

        ExecuteWorkflowJob::dispatch($workflow, $context);

        $workflow->update(['last_triggered_at' => now()]);
    }
}
