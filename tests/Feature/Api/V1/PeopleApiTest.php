<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\People;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/people')->assertUnauthorized();
});

it('can list people', function (): void {
    Sanctum::actingAs($this->user);

    $people = People::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/people')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'creation_source', 'custom_fields']]]);
});

it('can create a person', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/people', ['name' => 'John Doe'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'John Doe')
        ->assertJsonPath('data.creation_source', CreationSource::API->value);

    $this->assertDatabaseHas('people', ['name' => 'John Doe', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/people', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('can show a person', function (): void {
    Sanctum::actingAs($this->user);

    $person = People::factory()->for($this->team)->create();

    $this->getJson("/api/v1/people/{$person->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $person->id);
});

it('can update a person', function (): void {
    Sanctum::actingAs($this->user);

    $person = People::factory()->for($this->team)->create();

    $this->putJson("/api/v1/people/{$person->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('can delete a person', function (): void {
    Sanctum::actingAs($this->user);

    $person = People::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/people/{$person->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('people', ['id' => $person->id]);
});

it('scopes people to current team', function (): void {
    Sanctum::actingAs($this->user);

    $ownPerson = People::factory()->for($this->team)->create();
    $otherPerson = People::factory()->create();

    $this->getJson('/api/v1/people')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownPerson->id);
});
