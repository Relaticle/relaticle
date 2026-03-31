<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Task;

use App\Actions\Task\DeleteTask;
use App\Ai\Tools\Chat\BaseWriteDeleteTool;
use App\Models\Task;

final class DeleteTaskTool extends BaseWriteDeleteTool
{
    public function description(): string
    {
        return 'Propose deleting a task. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return Task::class;
    }

    protected function actionClass(): string
    {
        return DeleteTask::class;
    }

    protected function entityLabel(): string
    {
        return 'Task';
    }

    protected function entityType(): string
    {
        return 'task';
    }

    protected function nameAttribute(): string
    {
        return 'title';
    }
}
