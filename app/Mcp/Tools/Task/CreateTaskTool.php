<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Actions\Task\CreateTask;
use App\Enums\CreationSource;
use App\Http\Resources\V1\TaskResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new task in the CRM.')]
final class CreateTaskTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The task title.')->required(),
        ];
    }

    public function handle(Request $request, CreateTask $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $task = $action->execute($user, $validated, CreationSource::API);

        return Response::text(
            new TaskResource($task->loadMissing('customFieldValues.customField'))->toJson(JSON_PRETTY_PRINT)
        );
    }
}
