<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Opportunity;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/opportunities')->assertUnauthorized();
});

it('can list opportunities', function (): void {
    Sanctum::actingAs($this->user);

    $opportunities = Opportunity::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/opportunities')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'creation_source', 'custom_fields']]]);
});

it('can create an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/opportunities', ['name' => 'Big Deal'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Big Deal')
        ->assertJsonPath('data.creation_source', CreationSource::API->value);

    $this->assertDatabaseHas('opportunities', ['name' => 'Big Deal', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/opportunities', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('can show an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $opportunity = Opportunity::factory()->for($this->team)->create();

    $this->getJson("/api/v1/opportunities/{$opportunity->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $opportunity->id);
});

it('can update an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $opportunity = Opportunity::factory()->for($this->team)->create();

    $this->putJson("/api/v1/opportunities/{$opportunity->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('can delete an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $opportunity = Opportunity::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/opportunities/{$opportunity->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('opportunities', ['id' => $opportunity->id]);
});

it('scopes opportunities to current team', function (): void {
    Sanctum::actingAs($this->user);

    $ownOpportunity = Opportunity::factory()->for($this->team)->create();
    $otherOpportunity = Opportunity::factory()->create();

    $this->getJson('/api/v1/opportunities')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownOpportunity->id);
});
