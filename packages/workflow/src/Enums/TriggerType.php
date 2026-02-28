<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum TriggerType: string
{
    case RecordEvent = 'record_event';
    case TimeBased = 'time_based';
    case Manual = 'manual';
    case Webhook = 'webhook';
}
