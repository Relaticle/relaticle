<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Task;

use App\Ai\Tools\Chat\BaseReadShowTool;
use App\Http\Resources\V1\TaskResource;
use App\Models\Task;

final class GetTaskTool extends BaseReadShowTool
{
    public function description(): string
    {
        return 'Get a single task by ID with full details.';
    }

    protected function modelClass(): string
    {
        return Task::class;
    }

    protected function resourceClass(): string
    {
        return TaskResource::class;
    }

    protected function entityLabel(): string
    {
        return 'Task';
    }

    /** @return array<int, string> */
    protected function eagerLoad(): array
    {
        return ['assignees', 'customFieldValues.customField.options'];
    }
}
