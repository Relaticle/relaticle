<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use App\Support\CustomFieldMerger;
use App\Support\TenantFkValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateTask
{
    public function __construct(
        private NotifyTaskAssignees $notifyAssignees,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function execute(User $user, Task $task, array $data): Task
    {
        abort_unless($user->can('update', $task), 403);

        TenantFkValidator::assertOwnedMany($user, $data, [
            'company_ids' => Company::class,
            'people_ids' => People::class,
            'opportunity_ids' => Opportunity::class,
        ]);

        $this->assertAssigneesInWorkspace($user, $data['assignee_ids'] ?? null);

        $attributes = Arr::only($data, ['title', 'custom_fields']);

        $attributes = CustomFieldMerger::merge($task, $attributes);

        $previousAssigneeIds = $task->assignees()->pluck('users.id')->all();

        $task = DB::transaction(function () use ($task, $attributes, $data): Task {
            $task->update($attributes);

            if (array_key_exists('company_ids', $data)) {
                $task->companies()->sync($data['company_ids']);
            }
            if (array_key_exists('people_ids', $data)) {
                $task->people()->sync($data['people_ids']);
            }
            if (array_key_exists('opportunity_ids', $data)) {
                $task->opportunities()->sync($data['opportunity_ids']);
            }
            if (array_key_exists('assignee_ids', $data)) {
                $task->assignees()->sync($data['assignee_ids']);
            }

            return $task->refresh();
        });

        $this->notifyAssignees->execute($task, $previousAssigneeIds);

        return $task->load('customFieldValues.customField.options');
    }

    private function assertAssigneesInWorkspace(User $user, mixed $assigneeIds): void
    {
        if (! is_array($assigneeIds) || $assigneeIds === []) {
            return;
        }

        $team = $user->currentTeam;

        if ($team === null) {
            throw ValidationException::withMessages(['team' => 'No active workspace.']);
        }

        $memberIds = $team->users()->pluck('users.id')->all();
        $memberIds[] = $team->user_id;
        $memberIdsStr = array_map(strval(...), $memberIds);

        foreach ($assigneeIds as $assigneeId) {
            throw_unless(
                in_array((string) $assigneeId, $memberIdsStr, true),
                ValidationException::withMessages([
                    'assignee_ids' => 'One or more assignees are not in your workspace.',
                ])
            );
        }
    }
}
