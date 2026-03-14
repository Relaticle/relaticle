<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Actions\Task\UpdateTask;
use App\Http\Resources\V1\TaskResource;
use App\Mcp\Tools\BaseUpdateTool;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing task in the CRM. Use the crm-schema resource to discover available custom fields.')]
#[IsIdempotent]
final class UpdateTaskTool extends BaseUpdateTool
{
    protected function modelClass(): string
    {
        return Task::class;
    }

    protected function actionClass(): string
    {
        return UpdateTask::class;
    }

    protected function resourceClass(): string
    {
        return TaskResource::class;
    }

    protected function entityType(): string
    {
        return 'task';
    }

    protected function entityLabel(): string
    {
        return 'task';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The task title.'),
        ];
    }

    protected function entityRules(User $user): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
