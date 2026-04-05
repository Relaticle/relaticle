<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Task;

use App\Actions\Task\CreateTask;
use Relaticle\Chat\Tools\BaseWriteCreateTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

final class CreateTaskTool extends BaseWriteCreateTool
{
    public function description(): string
    {
        return 'Propose creating a new task. Returns a proposal for user approval.';
    }

    protected function actionClass(): string
    {
        return CreateTask::class;
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

    protected function extractActionData(Request $request): array
    {
        return ['title' => (string) $request->string('title')];
    }

    protected function buildDisplayData(Request $request): array
    {
        $title = (string) $request->string('title');

        return [
            'title' => 'Create Task',
            'summary' => "Create task \"{$title}\"",
            'fields' => [['label' => 'Title', 'value' => $title]],
        ];
    }
}
