<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Task;

use App\Actions\Task\ListTasks;
use App\Http\Resources\V1\TaskResource;
use App\Mcp\Tools\Concerns\ChecksTokenAbility;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List tasks in the CRM with optional search and pagination.')]
#[IsReadOnly]
#[IsIdempotent]
final class ListTasksTool extends Tool
{
    use ChecksTokenAbility;

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search tasks by title.'),
            'assigned_to_me' => $schema->boolean()->description('Filter tasks assigned to the current user.'),
            'per_page' => $schema->integer()->description('Results per page (default 15, max 100).')->default(15),
            'page' => $schema->integer()->description('Page number.')->default(1),
        ];
    }

    public function handle(Request $request, ListTasks $action): Response
    {
        $this->ensureTokenCan('read');

        /** @var User $user */
        $user = auth()->user();

        $filters = array_filter([
            'title' => $request->get('search'),
            'assigned_to_me' => $request->get('assigned_to_me') ? '1' : null,
        ]);

        $tasks = $action->execute(
            user: $user,
            perPage: (int) $request->get('per_page', 15),
            filters: $filters,
            page: $request->get('page') ? (int) $request->get('page') : null,
        );

        return Response::text(
            TaskResource::collection($tasks)->toJson(JSON_PRETTY_PRINT)
        );
    }
}
