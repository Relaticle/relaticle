<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\App\Exports;

use App\Enums\CustomFields\Company as CompanyCustomField;
use App\Filament\App\Exports\CompanyExporter;
use App\Filament\App\Resources\CompanyResource\Pages\ListCompanies;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Data\CustomFieldSectionData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;

uses(RefreshDatabase::class);

test('exports company records with basic functionality', function () {
    // Create a team and set up a user that belongs to it
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $user->teams()->attach($team);

    $this->actingAs($user);
    Filament::setTenant($team);

    // Create companies
    $companies = Company::factory()->count(3)->create(['team_id' => $team->id]);

    $livewireTest = Livewire::test(ListCompanies::class);
    $livewireTest->assertSuccessful();
    $livewireTest->assertTableBulkActionExists('export');

    // Test export
    $livewireTest->callTableBulkAction('export', $companies)
        ->assertHasNoTableBulkActionErrors();

    $exportModel = Export::latest()->first();
    expect($exportModel)->not->toBeNull()
        ->and($exportModel->exporter)->toBe(CompanyExporter::class)
        ->and($exportModel->file_disk)->toBe('local')
        ->and($exportModel->team_id)->toBe($team->id);
});

test('exports respect team scoping', function () {
    // Create two teams
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    // User belongs to both teams but has team1 as current
    $user = User::factory()->create(['current_team_id' => $team1->id]);
    $user->teams()->attach([$team1->id, $team2->id]);

    $this->actingAs($user);
    Filament::setTenant($team1);

    // Create companies for first team
    $team1Companies = Company::factory()
        ->count(2)
        ->create(['team_id' => $team1->id]);

    // Test export with team1
    Livewire::test(ListCompanies::class)
        ->callTableBulkAction('export', $team1Companies)
        ->assertHasNoTableBulkActionErrors();

    $exportModel = Export::latest()->first();
    expect($exportModel->team_id)->toBe($team1->id);
});

test('exports include company custom fields', function () {
    // Create a team and set up a user
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $user->teams()->attach($team);

    $this->actingAs($user);
    Filament::setTenant($team);

    // Create a custom test field
    $migrator = app(CustomsFieldsMigrators::class);
    $migrator->setTenantId($team->id);

    $fieldData = new CustomFieldData(
        name: 'Test Custom Field',
        code: 'test_field',
        type: CustomFieldType::TEXT,
        section: new CustomFieldSectionData(
            name: 'General',
            code: 'general',
            type: CustomFieldSectionType::HEADLESS
        ),
        systemDefined: false,
        width: CustomFieldWidth::_50,
        settings: new CustomFieldSettingsData(
            list_toggleable_hidden: false
        )
    );

    $fieldMigrator = $migrator->new(
        model: Company::class,
        fieldData: $fieldData
    );

    $fieldMigrator->create();

    // Get the available export columns from the exporter
    $columns = CompanyExporter::getColumns();

    // Convert to array of column names/labels for easier testing
    $columnLabels = collect($columns)->map(fn ($column) => $column->getLabel())->all();

    // Verify our custom fields are in the export columns
    expect($columnLabels)->toContain('Test Custom Field')
        ->and($columnLabels)->toContain('ICP')
        ->and($columnLabels)->toContain('Domain Name')
        ->and($columnLabels)->toContain('LinkedIn');

    // Check for standard company custom fields from the enum - these are already created by CreateTeamCustomFields

    // Ensure the count of columns includes standard columns plus custom fields
    $standardColumnCount = 10; // This should match the count of non-custom-field columns in CompanyExporter
    $customFieldCount = count(CompanyCustomField::cases()) + 1; // All enum cases plus our test field

    expect(count($columns))->toBeGreaterThanOrEqual($standardColumnCount + $customFieldCount);
});
