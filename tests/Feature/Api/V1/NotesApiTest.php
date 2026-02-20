<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Note;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/notes')->assertUnauthorized();
});

it('can list notes', function (): void {
    Sanctum::actingAs($this->user);

    $notes = Note::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/notes')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'creation_source', 'custom_fields']]]);
});

it('can create a note', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/notes', ['title' => 'Meeting notes'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Meeting notes')
        ->assertJsonPath('data.creation_source', CreationSource::API->value);

    $this->assertDatabaseHas('notes', ['title' => 'Meeting notes', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/notes', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('can show a note', function (): void {
    Sanctum::actingAs($this->user);

    $note = Note::factory()->for($this->team)->create();

    $this->getJson("/api/v1/notes/{$note->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $note->id);
});

it('can update a note', function (): void {
    Sanctum::actingAs($this->user);

    $note = Note::factory()->for($this->team)->create();

    $this->putJson("/api/v1/notes/{$note->id}", ['title' => 'Updated Title'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('can delete a note', function (): void {
    Sanctum::actingAs($this->user);

    $note = Note::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/notes/{$note->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('notes', ['id' => $note->id]);
});

it('scopes notes to current team', function (): void {
    Sanctum::actingAs($this->user);

    $ownNote = Note::factory()->for($this->team)->create();
    $otherNote = Note::factory()->create();

    $this->getJson('/api/v1/notes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownNote->id);
});
