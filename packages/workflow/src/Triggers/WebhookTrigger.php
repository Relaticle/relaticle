<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers;

use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\Contracts\WorkflowTrigger;

class WebhookTrigger implements WorkflowTrigger
{
    public static function type(): TriggerType
    {
        return TriggerType::Webhook;
    }

    /**
     * Trigger a webhook workflow, dispatching the execution job.
     *
     * @param  Workflow  $workflow  The workflow to trigger
     * @param  array<string, mixed>  $payload  The incoming webhook payload
     *
     * @throws \InvalidArgumentException If the workflow is inactive or not a webhook trigger type
     */
    public function trigger(Workflow $workflow, array $payload = []): void
    {
        if (! $workflow->status->canTrigger()) {
            throw new \InvalidArgumentException('Workflow is not active.');
        }

        if ($workflow->trigger_type !== TriggerType::Webhook) {
            throw new \InvalidArgumentException('Workflow is not a webhook trigger type.');
        }

        ExecuteWorkflowJob::dispatch($workflow, ['webhook' => $payload]);

        $workflow->update(['last_triggered_at' => now()]);
    }
}
