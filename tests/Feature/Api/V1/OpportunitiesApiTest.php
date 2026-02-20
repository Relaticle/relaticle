<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
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

    $seeded = Opportunity::query()->where('team_id', $this->team->id)->count();
    Opportunity::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/opportunities')
        ->assertOk()
        ->assertJsonCount($seeded + 3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'creation_source', 'custom_fields']]]);
});

it('can create an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/opportunities', ['name' => 'Big Deal'])
        ->assertCreated()
        ->assertValid()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('name', 'Big Deal')
                ->where('creation_source', CreationSource::API->value)
                ->whereType('id', 'string')
                ->whereType('created_at', 'string')
                ->whereType('custom_fields', 'array')
                ->missing('team_id')
                ->missing('creator_id')
                ->etc()
            )
        );

    $this->assertDatabaseHas('opportunities', ['name' => 'Big Deal', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/opportunities', [])
        ->assertUnprocessable()
        ->assertInvalid(['name']);
});

it('can show an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $opportunity = Opportunity::factory()->for($this->team)->create(['name' => 'Show Test']);

    $this->getJson("/api/v1/opportunities/{$opportunity->id}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('id', $opportunity->id)
                ->where('name', 'Show Test')
                ->whereType('creation_source', 'string')
                ->whereType('custom_fields', 'array')
                ->missing('team_id')
                ->missing('creator_id')
                ->etc()
            )
        );
});

it('can update an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $opportunity = Opportunity::factory()->for($this->team)->create();

    $this->putJson("/api/v1/opportunities/{$opportunity->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertValid()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('id', $opportunity->id)
                ->where('name', 'Updated Name')
                ->etc()
            )
        );
});

it('can delete an opportunity', function (): void {
    Sanctum::actingAs($this->user);

    $opportunity = Opportunity::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/opportunities/{$opportunity->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('opportunities', ['id' => $opportunity->id]);
});

it('scopes opportunities to current team', function (): void {
    $otherOpportunity = Opportunity::withoutEvents(fn () => Opportunity::factory()->create(['team_id' => Team::factory()->create()->id]));

    Sanctum::actingAs($this->user);

    $ownOpportunity = Opportunity::factory()->for($this->team)->create();

    $response = $this->getJson('/api/v1/opportunities');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($ownOpportunity->id);
    expect($ids)->not->toContain($otherOpportunity->id);
});

describe('cross-tenant isolation', function (): void {
    it('cannot show an opportunity from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherOpportunity = Opportunity::withoutEvents(fn () => Opportunity::factory()->create(['team_id' => $otherTeam->id]));

        $this->getJson("/api/v1/opportunities/{$otherOpportunity->id}")
            ->assertNotFound();
    });

    it('cannot update an opportunity from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherOpportunity = Opportunity::withoutEvents(fn () => Opportunity::factory()->create(['team_id' => $otherTeam->id]));

        $this->putJson("/api/v1/opportunities/{$otherOpportunity->id}", ['name' => 'Hacked'])
            ->assertNotFound();
    });

    it('cannot delete an opportunity from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherOpportunity = Opportunity::withoutEvents(fn () => Opportunity::factory()->create(['team_id' => $otherTeam->id]));

        $this->deleteJson("/api/v1/opportunities/{$otherOpportunity->id}")
            ->assertNotFound();
    });
});
