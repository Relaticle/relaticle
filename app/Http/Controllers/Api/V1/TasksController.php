<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Task\CreateTask;
use App\Actions\Task\DeleteTask;
use App\Actions\Task\ListTasks;
use App\Actions\Task\UpdateTask;
use App\Enums\CreationSource;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class TasksController
{
    public function index(Request $request, ListTasks $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $tasks = $action->execute($user, $request->query());

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, CreateTask $action): TaskResource
    {
        /** @var User $user */
        $user = $request->user();

        $task = $action->execute($user, $request->validated(), CreationSource::API);

        return new TaskResource($task->loadMissing('customFieldValues.customField'));
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task->loadMissing('customFieldValues.customField'));
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $action): TaskResource
    {
        /** @var User $user */
        $user = $request->user();

        $task = $action->execute($user, $task, $request->validated());

        return new TaskResource($task->loadMissing('customFieldValues.customField'));
    }

    public function destroy(Request $request, Task $task, DeleteTask $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, $task);

        return response()->json(null, 204);
    }
}
