<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum WorkflowRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
