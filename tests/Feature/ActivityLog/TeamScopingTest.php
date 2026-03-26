<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\ActivityLog\Models\Activity;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('only shows activities for the current team', function (): void {
    Company::factory()->for($this->team)->create(['name' => 'Team A Company']);

    $otherUser = User::factory()->withTeam()->create();
    $otherTeam = $otherUser->currentTeam;

    Filament::setTenant($otherTeam);
    $this->actingAs($otherUser);

    Company::factory()->for($otherTeam)->create(['name' => 'Team B Company']);

    Filament::setTenant($this->team);
    $this->actingAs($this->user);

    $activities = Activity::all();

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->team_id)->toBe($this->team->getKey());
});

it('stores team_id from subject model', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Test']);

    $activity = Activity::withoutGlobalScopes()
        ->where('subject_type', 'company')
        ->where('subject_id', $company->getKey())
        ->first();

    expect($activity->team_id)->toBe($this->team->getKey());
});
