<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;

abstract class BaseAction implements WorkflowAction
{
    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [];
    }
}
