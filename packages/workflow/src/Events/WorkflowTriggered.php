<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Relaticle\Workflow\Models\Workflow;

class WorkflowTriggered
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Workflow $workflow,
        public readonly array $context = [],
    ) {}
}
