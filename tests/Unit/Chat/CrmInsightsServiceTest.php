<?php

declare(strict_types=1);

use App\Features\OnboardSeed;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;
use Relaticle\Chat\Data\CrmInsight;
use Relaticle\Chat\Services\CrmInsightsService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

mutates(CrmInsightsService::class);

beforeEach(function (): void {
    Feature::define(OnboardSeed::class, false);
    Cache::flush();
});

it('returns no insights for an empty team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $insights = (new CrmInsightsService)->forTeam($user->currentTeam);

    expect($insights)->toBeEmpty();
});

it('flags stalled opportunities updated more than 30 days ago', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    Opportunity::factory()
        ->for($team)
        ->count(3)
        ->create(['updated_at' => now()->subDays(45)]);

    $insights = (new CrmInsightsService)->forTeam($team);
    $stalled = $insights->firstWhere('key', 'stalled-deals');

    expect($stalled)
        ->toBeInstanceOf(CrmInsight::class)
        ->and($stalled->count)->toBe(3)
        ->and($stalled->severity)->toBe('warning');
});

it('does not flag stalled opportunities when none exceed the threshold', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    Opportunity::factory()
        ->for($team)
        ->count(2)
        ->create(['updated_at' => now()->subDays(10)]);

    $insights = (new CrmInsightsService)->forTeam($team);

    expect($insights->firstWhere('key', 'stalled-deals'))->toBeNull();
});

it('flags cold contacts not updated in 60+ days', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    People::factory()
        ->for($team)
        ->count(4)
        ->create(['updated_at' => now()->subDays(75)]);

    $insights = (new CrmInsightsService)->forTeam($team);
    $cold = $insights->firstWhere('key', 'cold-contacts');

    expect($cold)
        ->toBeInstanceOf(CrmInsight::class)
        ->and($cold->count)->toBe(4);
});

it('flags new companies created this week', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    Company::factory()
        ->for($team)
        ->count(2)
        ->create(['created_at' => now()]);

    $insights = (new CrmInsightsService)->forTeam($team);
    $newCompanies = $insights->firstWhere('key', 'new-companies');

    expect($newCompanies)
        ->toBeInstanceOf(CrmInsight::class)
        ->and($newCompanies->count)->toBe(2)
        ->and($newCompanies->severity)->toBe('info');
});

it('scopes insights to the team and ignores other teams', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->currentTeam;

    Opportunity::factory()
        ->for($otherTeam)
        ->count(5)
        ->create(['updated_at' => now()->subDays(45)]);

    $insights = (new CrmInsightsService)->forTeam($team);

    expect($insights->firstWhere('key', 'stalled-deals'))->toBeNull();
});

it('caches results for the configured TTL', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $service = new CrmInsightsService;

    $first = $service->forTeam($team);

    Opportunity::factory()
        ->for($team)
        ->create(['updated_at' => now()->subDays(45)]);

    $second = $service->forTeam($team);

    expect($first->count())->toBe($second->count());
});
