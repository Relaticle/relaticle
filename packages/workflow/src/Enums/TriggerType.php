<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum TriggerType: string
{
    case RecordEvent = 'record_event';
    case TimeBased = 'time_based';
    case Manual = 'manual';
    case Webhook = 'webhook';

    public function getLabel(): string
    {
        return match ($this) {
            self::RecordEvent => 'Record Event',
            self::TimeBased => 'Time Based',
            self::Manual => 'Manual',
            self::Webhook => 'Webhook',
        };
    }
}
