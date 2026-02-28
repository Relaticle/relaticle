<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Triggers\Contracts;

use Relaticle\Workflow\Enums\TriggerType;

interface WorkflowTrigger
{
    /**
     * Get the trigger type this handler is responsible for.
     */
    public static function type(): TriggerType;
}
