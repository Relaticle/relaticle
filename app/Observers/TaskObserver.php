<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;

final readonly class TaskObserver
{
    public function creating(Task $task): void
    {
        if (auth()->check()) {
            $task->creator_id = auth()->id();
            $task->team_id = auth()->user()->currentTeam->getKey();
        }
    }
}
