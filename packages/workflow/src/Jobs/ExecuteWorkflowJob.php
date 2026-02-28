<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Models\Workflow;

class ExecuteWorkflowJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Workflow $workflow,
        public readonly array $context = [],
    ) {
        $this->onQueue(config('workflow.queue', 'default'));
        $this->tries = (int) config('workflow.retry_attempts', 3);
    }

    public function handle(WorkflowExecutor $executor): void
    {
        $executor->execute($this->workflow, $this->context);
    }
}
