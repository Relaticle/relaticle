<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Relaticle\Workflow\Models\WorkflowRun;

class WorkflowRunCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowRun $run,
    ) {}
}
