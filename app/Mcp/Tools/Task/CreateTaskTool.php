<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Actions\Task\CreateTask;
use App\Http\Resources\V1\TaskResource;
use App\Mcp\Tools\BaseCreateTool;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new task in the CRM. Use the crm-schema resource to discover available custom fields.')]
final class CreateTaskTool extends BaseCreateTool
{
    protected function actionClass(): string
    {
        return CreateTask::class;
    }

    protected function resourceClass(): string
    {
        return TaskResource::class;
    }

    protected function entityType(): string
    {
        return 'task';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The task title.')->required(),
        ];
    }

    protected function entityRules(User $user): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
