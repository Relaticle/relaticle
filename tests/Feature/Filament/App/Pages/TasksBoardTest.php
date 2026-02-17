<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Filament\Pages\TasksBoard;
use App\Listeners\CreateTeamCustomFields;
use App\Models\CustomField;
use App\Models\Task;
use App\Models\User;
use Filament\Facades\Filament;
use Laravel\Jetstream\Events\TeamCreated;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);

    $this->team = $this->user->personalTeam();
    app(CreateTeamCustomFields::class)->handle(new TeamCreated($this->team));

    Filament::setTenant($this->team);

    $this->statusField = CustomField::query()
        ->forEntity(Task::class)
        ->where('code', TaskField::STATUS)
        ->first();
});

function getTaskBoardRecords(): array
{
    $component = livewire(TasksBoard::class);
    $board = $component->instance()->getBoard();

    return $board->getBatchedBoardRecords();
}

it('can render the board page', function (): void {
    livewire(TasksBoard::class)
        ->assertOk();
});

it('displays tasks in the correct board columns', function (): void {
    $todo = $this->statusField->options->firstWhere('name', 'To do');
    $done = $this->statusField->options->firstWhere('name', 'Done');

    $todoTask = Task::factory()->for($this->team)->create();
    $todoTask->saveCustomFieldValue($this->statusField, $todo->getKey());

    $doneTask = Task::factory()->for($this->team)->create();
    $doneTask->saveCustomFieldValue($this->statusField, $done->getKey());

    $records = getTaskBoardRecords();

    expect($records[(string) $todo->getKey()]->pluck('id'))
        ->toContain($todoTask->id)
        ->and($records[(string) $done->getKey()]->pluck('id'))
        ->toContain($doneTask->id);
});

it('does not show tasks from other teams', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTask = Task::factory()->for($otherUser->personalTeam())->create();

    $records = getTaskBoardRecords();
    $allRecordIds = collect($records)->flatten()->pluck('id');

    expect($allRecordIds)->not->toContain($otherTask->id);
});

it('moves a card between columns via moveCard', function (): void {
    $todo = $this->statusField->options->firstWhere('name', 'To do');
    $inProgress = $this->statusField->options->firstWhere('name', 'In progress');

    $task = Task::factory()->for($this->team)->create();
    $task->saveCustomFieldValue($this->statusField, $todo->getKey());

    livewire(TasksBoard::class)
        ->call('moveCard', (string) $task->id, (string) $inProgress->getKey())
        ->assertDispatched('kanban-card-moved');

    $updatedValue = $task->fresh()->customFieldValues()
        ->where('custom_field_id', $this->statusField->getKey())
        ->value($this->statusField->getValueColumn());

    expect($updatedValue)->toBe($inProgress->getKey());
});
