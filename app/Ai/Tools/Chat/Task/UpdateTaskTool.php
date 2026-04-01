<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Task;

use App\Actions\Task\UpdateTask;
use App\Ai\Tools\Chat\BaseWriteUpdateTool;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Tools\Request;

final class UpdateTaskTool extends BaseWriteUpdateTool
{
    public function description(): string
    {
        return 'Propose updating an existing task. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return Task::class;
    }

    protected function actionClass(): string
    {
        return UpdateTask::class;
    }

    protected function entityType(): string
    {
        return 'task';
    }

    protected function entityLabel(): string
    {
        return 'Task';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return ['title' => $schema->string()->description('The new task title.')];
    }

    protected function extractActionData(Request $request): array
    {
        return array_filter(['title' => $request['title'] ?? null], fn (mixed $v): bool => $v !== null);
    }

    protected function buildDisplayData(Request $request, Model $model): array
    {
        $fields = [];
        if (($request['title'] ?? null) !== null) {
            $fields[] = ['label' => 'Title', 'old' => $model->getAttribute('title'), 'new' => $request['title']];
        }

        return [
            'title' => 'Update Task',
            'summary' => "Update task \"{$model->getAttribute('title')}\"",
            'fields' => $fields,
        ];
    }
}
