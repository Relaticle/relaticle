<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Relaticle\Workflow\Models\WorkflowNode;
use Relaticle\Workflow\Models\WorkflowRun;

class ExecuteStepJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly WorkflowRun $run,
        public readonly WorkflowNode $node,
        public readonly array $context = [],
    ) {
        $this->onQueue(config('workflow.queue', 'default'));
    }

    public function handle(): void
    {
        // Reserved for future async per-step execution.
        // Currently, all step execution is handled synchronously
        // by the WorkflowExecutor.
    }
}
