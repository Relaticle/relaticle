<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/tasks')->assertUnauthorized();
});

it('can list tasks', function (): void {
    Sanctum::actingAs($this->user);

    $tasks = Task::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/tasks')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'creation_source', 'custom_fields']]]);
});

it('can create a task', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/tasks', ['title' => 'Fix bug'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Fix bug')
        ->assertJsonPath('data.creation_source', CreationSource::API->value);

    $this->assertDatabaseHas('tasks', ['title' => 'Fix bug', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/tasks', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('can show a task', function (): void {
    Sanctum::actingAs($this->user);

    $task = Task::factory()->for($this->team)->create();

    $this->getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id);
});

it('can update a task', function (): void {
    Sanctum::actingAs($this->user);

    $task = Task::factory()->for($this->team)->create();

    $this->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Updated Title'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('can delete a task', function (): void {
    Sanctum::actingAs($this->user);

    $task = Task::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

it('scopes tasks to current team', function (): void {
    Sanctum::actingAs($this->user);

    $ownTask = Task::factory()->for($this->team)->create();
    $otherTask = Task::factory()->create();

    $this->getJson('/api/v1/tasks')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownTask->id);
});
