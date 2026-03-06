<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum NodeType: string
{
    case Trigger = 'trigger';
    case Action = 'action';
    case Condition = 'condition';
    case Filter = 'filter';
    case Switch = 'switch';
    case Delay = 'delay';
    case Loop = 'loop';
    case Stop = 'stop';
}
