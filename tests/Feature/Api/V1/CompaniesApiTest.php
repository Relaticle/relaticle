<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\CustomFieldSection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/companies')->assertUnauthorized();
});

it('can list companies', function (): void {
    Sanctum::actingAs($this->user);

    $seeded = Company::query()->where('team_id', $this->team->id)->count();
    Company::factory(3)->for($this->team)->create();

    $this->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonCount($seeded + 3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'creation_source', 'custom_fields']]]);
});

it('can create a company', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/companies', ['name' => 'Acme Corp'])
        ->assertCreated()
        ->assertValid()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('name', 'Acme Corp')
                ->where('creation_source', CreationSource::API->value)
                ->whereType('id', 'string')
                ->whereType('created_at', 'string')
                ->whereType('custom_fields', 'array')
                ->etc()
            )
        );

    $this->assertDatabaseHas('companies', ['name' => 'Acme Corp', 'team_id' => $this->team->id]);
});

it('validates required fields on create', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/companies', [])
        ->assertUnprocessable()
        ->assertInvalid(['name']);
});

it('can show a company', function (): void {
    Sanctum::actingAs($this->user);

    $company = Company::factory()->for($this->team)->create(['name' => 'Show Test']);

    $this->getJson("/api/v1/companies/{$company->id}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('id', $company->id)
                ->where('name', 'Show Test')
                ->whereType('creation_source', 'string')
                ->whereType('custom_fields', 'array')
                ->missing('team_id')
                ->missing('creator_id')
                ->etc()
            )
        );
});

it('can update a company', function (): void {
    Sanctum::actingAs($this->user);

    $company = Company::factory()->for($this->team)->create();

    $this->putJson("/api/v1/companies/{$company->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertValid()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->where('id', $company->id)
                ->where('name', 'Updated Name')
                ->etc()
            )
        );
});

it('can delete a company', function (): void {
    Sanctum::actingAs($this->user);

    $company = Company::factory()->for($this->team)->create();

    $this->deleteJson("/api/v1/companies/{$company->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('companies', ['id' => $company->id]);
});

it('scopes companies to current team', function (): void {
    $otherCompany = Company::withoutEvents(fn () => Company::factory()->create(['team_id' => Team::factory()->create()->id]));

    Sanctum::actingAs($this->user);

    $ownCompany = Company::factory()->for($this->team)->create();

    $response = $this->getJson('/api/v1/companies');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($ownCompany->id);
    expect($ids)->not->toContain($otherCompany->id);
});

describe('cross-tenant isolation', function (): void {
    it('cannot show a company from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherCompany = Company::withoutEvents(fn () => Company::factory()->create(['team_id' => $otherTeam->id]));

        $this->getJson("/api/v1/companies/{$otherCompany->id}")
            ->assertNotFound();
    });

    it('cannot update a company from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherCompany = Company::withoutEvents(fn () => Company::factory()->create(['team_id' => $otherTeam->id]));

        $this->putJson("/api/v1/companies/{$otherCompany->id}", ['name' => 'Hacked'])
            ->assertNotFound();
    });

    it('cannot delete a company from another team', function (): void {
        Sanctum::actingAs($this->user);

        $otherTeam = Team::factory()->create();
        $otherCompany = Company::withoutEvents(fn () => Company::factory()->create(['team_id' => $otherTeam->id]));

        $this->deleteJson("/api/v1/companies/{$otherCompany->id}")
            ->assertNotFound();
    });
});

describe('includes', function (): void {
    it('can include relations on list endpoint', function (): void {
        Sanctum::actingAs($this->user);

        Company::factory()->for($this->team)->create();

        $this->getJson('/api/v1/companies?include=creator')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data.0.creator')
                ->etc()
            );
    });

    it('can include relations on show endpoint', function (): void {
        Sanctum::actingAs($this->user);

        $company = Company::factory()->for($this->team)->create();

        $this->getJson("/api/v1/companies/{$company->id}?include=creator")
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data.creator')
                ->etc()
            );
    });

    it('does not include relations when not requested', function (): void {
        Sanctum::actingAs($this->user);

        $company = Company::factory()->for($this->team)->create();

        $response = $this->getJson("/api/v1/companies/{$company->id}")
            ->assertOk();

        expect($response->json('data.creator'))->toBeNull();
    });

    it('rejects disallowed includes on list endpoint', function (): void {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/companies?include=secret')
            ->assertStatus(400);
    });
});

describe('custom fields', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::create([
            'tenant_id' => $this->team->id,
            'entity_type' => 'company',
            'name' => 'General',
            'code' => 'general',
            'type' => 'section',
            'sort_order' => 1,
            'active' => true,
        ]);
    });

    it('can create a company with custom fields', function (): void {
        Sanctum::actingAs($this->user);

        CustomField::create([
            'tenant_id' => $this->team->id,
            'custom_field_section_id' => $this->section->id,
            'entity_type' => 'company',
            'code' => 'industry',
            'name' => 'Industry',
            'type' => 'text',
            'sort_order' => 1,
            'active' => true,
            'validation_rules' => [],
        ]);

        $this->postJson('/api/v1/companies', [
            'name' => 'Acme Corp',
            'custom_fields' => [
                'industry' => 'Technology',
            ],
        ])
            ->assertCreated()
            ->assertValid()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', fn (AssertableJson $json) => $json
                    ->where('name', 'Acme Corp')
                    ->where('custom_fields.industry', 'Technology')
                    ->etc()
                )
            );
    });

    it('can update a company with custom fields', function (): void {
        Sanctum::actingAs($this->user);

        CustomField::create([
            'tenant_id' => $this->team->id,
            'custom_field_section_id' => $this->section->id,
            'entity_type' => 'company',
            'code' => 'industry',
            'name' => 'Industry',
            'type' => 'text',
            'sort_order' => 1,
            'active' => true,
            'validation_rules' => [],
        ]);

        $company = Company::factory()->for($this->team)->create();

        $this->putJson("/api/v1/companies/{$company->id}", [
            'name' => 'Updated Name',
            'custom_fields' => [
                'industry' => 'Finance',
            ],
        ])
            ->assertOk()
            ->assertValid()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', fn (AssertableJson $json) => $json
                    ->where('name', 'Updated Name')
                    ->where('custom_fields.industry', 'Finance')
                    ->etc()
                )
            );
    });

    it('validates custom field values on create', function (): void {
        Sanctum::actingAs($this->user);

        CustomField::create([
            'tenant_id' => $this->team->id,
            'custom_field_section_id' => $this->section->id,
            'entity_type' => 'company',
            'code' => 'annual_revenue',
            'name' => 'Annual Revenue',
            'type' => 'number',
            'sort_order' => 1,
            'active' => true,
            'validation_rules' => [
                ['name' => 'numeric', 'parameters' => []],
            ],
        ]);

        $this->postJson('/api/v1/companies', [
            'name' => 'Acme Corp',
            'custom_fields' => [
                'annual_revenue' => 'not-a-number',
            ],
        ])
            ->assertUnprocessable()
            ->assertInvalid(['custom_fields.annual_revenue']);
    });

    it('ignores unknown custom field codes', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/companies', [
            'name' => 'Acme Corp',
            'custom_fields' => [
                'nonexistent_field' => 'some value',
            ],
        ])
            ->assertCreated();
    });
});
