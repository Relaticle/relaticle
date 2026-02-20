<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

it('uses current team by default', function (): void {
    Sanctum::actingAs($this->user);

    $company = Company::factory()->for($this->team)->create();

    $this->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can switch team via X-Team-Id header', function (): void {
    $otherTeam = Team::factory()->create();
    $this->user->teams()->attach($otherTeam);

    Sanctum::actingAs($this->user);

    Company::factory()->for($this->team)->create();
    $otherCompany = Company::factory()->for($otherTeam)->create();

    $this->getJson('/api/v1/companies', ['X-Team-Id' => $otherTeam->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $otherCompany->id);
});

it('rejects X-Team-Id for team user does not belong to', function (): void {
    $foreignTeam = Team::factory()->create();

    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/companies', ['X-Team-Id' => $foreignTeam->id])
        ->assertForbidden();
});

it('returns 403 when user has no team', function (): void {
    $userWithoutTeam = User::factory()->create();
    $userWithoutTeam->current_team_id = null;
    $userWithoutTeam->save();

    Sanctum::actingAs($userWithoutTeam);

    $this->getJson('/api/v1/companies')
        ->assertForbidden();
});
