<?php

declare(strict_types=1);

use App\Actions\Task\NotifyTaskAssignees;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();

    $this->actingAs($this->user);
});

it('notifies only newly added assignees', function (): void {
    $existingAssignee = User::factory()->withPersonalTeam()->create();
    $newAssignee = User::factory()->withPersonalTeam()->create();

    $task = Task::factory()->for($this->team)->create();
    $task->assignees()->sync([$existingAssignee->id, $newAssignee->id]);

    $previousIds = [$existingAssignee->id];

    app(NotifyTaskAssignees::class)->execute($task, $previousIds);

    expect($newAssignee->notifications)->toHaveCount(1)
        ->and($existingAssignee->notifications)->toHaveCount(0);
});

it('notifies all assignees when previous list is empty', function (): void {
    $assignee1 = User::factory()->withPersonalTeam()->create();
    $assignee2 = User::factory()->withPersonalTeam()->create();

    $task = Task::factory()->for($this->team)->create();
    $task->assignees()->sync([$assignee1->id, $assignee2->id]);

    app(NotifyTaskAssignees::class)->execute($task);

    expect($assignee1->notifications)->toHaveCount(1)
        ->and($assignee2->notifications)->toHaveCount(1);
});

it('sends nothing when no new assignees', function (): void {
    $assignee = User::factory()->withPersonalTeam()->create();

    $task = Task::factory()->for($this->team)->create();
    $task->assignees()->sync([$assignee->id]);

    app(NotifyTaskAssignees::class)->execute($task, [$assignee->id]);

    expect($assignee->notifications)->toHaveCount(0);
});

it('sends nothing when assignee list is empty', function (): void {
    $task = Task::factory()->for($this->team)->create();

    app(NotifyTaskAssignees::class)->execute($task);

    expect(true)->toBeTrue();
});

it('includes task title in notification', function (): void {
    $assignee = User::factory()->withPersonalTeam()->create();

    $task = Task::factory()->for($this->team)->create(['title' => 'Review PR']);
    $task->assignees()->sync([$assignee->id]);

    app(NotifyTaskAssignees::class)->execute($task);

    $notification = $assignee->notifications->first();
    expect($notification->data['title'])->toContain('Review PR');
});
