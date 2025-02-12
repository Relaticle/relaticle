<?php

namespace App\Enums;

use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum TaskStatus: string
{
    use IsKanbanStatus;

    case todo = 'todo';
    case inProgress = 'In Progress';
    case done = 'Done';
}
