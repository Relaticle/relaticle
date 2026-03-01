<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Paused = 'paused';
    case Archived = 'archived';

    public function canTrigger(): bool
    {
        return $this === self::Live;
    }
}
