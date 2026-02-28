<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Relaticle\Workflow\Engine\WorkflowExecutor;
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
        public readonly string $resumeFromNodeId,
        public readonly array $context = [],
    ) {
        $this->onQueue(config('workflow.queue', 'default'));
    }

    public function handle(WorkflowExecutor $executor): void
    {
        $executor->resume($this->run, $this->resumeFromNodeId, $this->context);
    }
}
