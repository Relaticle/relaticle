<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
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

    $seeded = Task::query()->where('team_id', $this->team->id)->count();
    Task::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/tasks')
        ->assertOk()
        ->assertJsonCount($seeded + 3, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'creation_source', 'custom_fields']]]);
});

it('can create a task', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/tasks', ['title' => 'Fix bug'])
        ->assertCreated()
        ->assertValid()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('title', 'Fix bug')
                ->where('creation_source', CreationSource::API->value)
                ->whereType('id', 'string')
                ->whereType('created_at', 'string')
                ->whereType('custom_fields', 'array')
                ->missing('team_id')
                ->missing('creator_id')
                ->etc()
            )
        );

    $this->assertDatabaseHas('tasks', ['title' => 'Fix bug', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/tasks', [])
        ->assertUnprocessable()
        ->assertInvalid(['title']);
});

it('can show a task', function (): void {
    Sanctum::actingAs($this->user);

    $task = Task::factory()->for($this->team)->create(['title' => 'Show Test']);

    $this->getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('id', $task->id)
                ->where('title', 'Show Test')
                ->whereType('creation_source', 'string')
                ->whereType('custom_fields', 'array')
                ->missing('team_id')
                ->missing('creator_id')
                ->etc()
            )
        );
});

it('can update a task', function (): void {
    Sanctum::actingAs($this->user);

    $task = Task::factory()->for($this->team)->create();

    $this->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Updated Title'])
        ->assertOk()
        ->assertValid()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('id', $task->id)
                ->where('title', 'Updated Title')
                ->etc()
            )
        );
});

it('can delete a task', function (): void {
    Sanctum::actingAs($this->user);

    $task = Task::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

it('scopes tasks to current team', function (): void {
    $otherTask = Task::withoutEvents(fn () => Task::factory()->create(['team_id' => Team::factory()->create()->id]));

    Sanctum::actingAs($this->user);

    $ownTask = Task::factory()->for($this->team)->create();

    $response = $this->getJson('/api/v1/tasks');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($ownTask->id);
    expect($ids)->not->toContain($otherTask->id);
});

describe('cross-tenant isolation', function (): void {
    it('cannot show a task from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherTask = Task::withoutEvents(fn () => Task::factory()->create(['team_id' => $otherTeam->id]));

        $this->getJson("/api/v1/tasks/{$otherTask->id}")
            ->assertNotFound();
    });

    it('cannot update a task from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherTask = Task::withoutEvents(fn () => Task::factory()->create(['team_id' => $otherTeam->id]));

        $this->putJson("/api/v1/tasks/{$otherTask->id}", ['title' => 'Hacked'])
            ->assertNotFound();
    });

    it('cannot delete a task from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherTask = Task::withoutEvents(fn () => Task::factory()->create(['team_id' => $otherTeam->id]));

        $this->deleteJson("/api/v1/tasks/{$otherTask->id}")
            ->assertNotFound();
    });
});
