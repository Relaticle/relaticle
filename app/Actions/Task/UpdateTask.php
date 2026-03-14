<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentNotifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTask
{
    public function __construct(
        private TaskAssignmentNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function execute(User $user, Task $task, array $data): Task
    {
        abort_unless($user->can('update', $task), 403);

        $attributes = Arr::only($data, ['title', 'custom_fields']);

        return DB::transaction(function () use ($task, $attributes): Task {
            $task->update($attributes);

            $this->notifier->notifyNewAssignees($task);

            return $task->refresh();
        });
    }
}
