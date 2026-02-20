<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Actions\Task\UpdateTask;
use App\Http\Resources\V1\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing task in the CRM.')]
#[IsIdempotent]
final class UpdateTaskTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The task ID to update.')->required(),
            'title' => $schema->string()->description('The task title.'),
        ];
    }

    public function handle(Request $request, UpdateTask $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'id' => ['required', 'string'],
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        /** @var Task $task */
        $task = Task::query()->findOrFail($validated['id']);
        unset($validated['id']);

        $task = $action->execute($user, $task, $validated);

        return Response::text(
            (new TaskResource($task->loadMissing('customFieldValues.customField')))->toJson(JSON_PRETTY_PRINT)
        );
    }
}
