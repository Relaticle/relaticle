<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\App\Exports;

use App\Filament\App\Exports\CompanyExporter;
use App\Filament\App\Resources\CompanyResource\Pages\ListCompanies;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
