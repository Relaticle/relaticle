<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Task;

use App\Actions\Task\ListTasks;
use Relaticle\Chat\Tools\BaseReadListTool;
use App\Http\Resources\V1\TaskResource;

final class ListTasksTool extends BaseReadListTool
{
    public function description(): string
    {
        return 'List tasks with optional search and pagination.';
    }

    protected function actionClass(): string
    {
        return ListTasks::class;
    }

    protected function resourceClass(): string
    {
        return TaskResource::class;
    }

    protected function searchFilterName(): string
    {
        return 'title';
    }
}
