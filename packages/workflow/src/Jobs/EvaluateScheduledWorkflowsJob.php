<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\ScheduledTrigger;

class EvaluateScheduledWorkflowsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(ScheduledTrigger $trigger): void
    {
        $workflows = Workflow::where('trigger_type', TriggerType::TimeBased)
            ->where('is_active', true)
            ->get();

        foreach ($workflows as $workflow) {
            if ($trigger->evaluate($workflow)) {
                ExecuteWorkflowJob::dispatch($workflow, []);
                $workflow->update(['last_triggered_at' => now()]);
            }
        }
    }
}
