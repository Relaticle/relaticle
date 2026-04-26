<?php

declare(strict_types=1);

use App\Features\OnboardSeed;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Relaticle\Chat\Data\CrmInsight;
use Relaticle\Chat\Services\CrmInsightsService;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;
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

it('caches results across calls when no writes occur', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $service = new CrmInsightsService;

    Opportunity::factory()
        ->for($team)
        ->create(['updated_at' => now()->subDays(45)]);

    $first = $service->forTeam($team);
    $second = $service->forTeam($team);

    expect($first->count())->toBe($second->count())
        ->and($first->firstWhere('key', 'stalled-deals'))->not->toBeNull();
});

it('invalidates insights cache on opportunity write', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $service = app(CrmInsightsService::class);

    $opp = Opportunity::factory()->for($team)->create(['updated_at' => now()->subDays(45)]);
    $first = $service->forTeam($team);
    expect($first->firstWhere('key', 'stalled-deals'))->not->toBeNull();

    $opp->update(['updated_at' => now()]);

    $second = $service->forTeam($team);
    expect($second->firstWhere('key', 'stalled-deals'))->toBeNull();
});

it('flags overdue tasks via due_date custom field', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $tasks = Task::factory()->for($team)->count(2)->create();
    $dueFieldId = (string) Str::ulid();
    CustomField::query()->create([
        'id' => $dueFieldId,
        'code' => 'due_date',
        'name' => 'Due Date',
        'type' => 'date',
        'entity_type' => Task::class,
        'tenant_id' => $team->id,
        'active' => true,
        'system_defined' => true,
    ]);
    foreach ($tasks as $task) {
        CustomFieldValue::query()->create([
            'id' => (string) Str::ulid(),
            'entity_type' => Task::class,
            'entity_id' => $task->id,
            'custom_field_id' => $dueFieldId,
            'tenant_id' => $team->id,
            'date_value' => now()->subDays(2)->toDateString(),
        ]);
    }

    $insights = (new CrmInsightsService)->forTeam($team);
    expect($insights->firstWhere('key', 'overdue-tasks'))
        ->toBeInstanceOf(CrmInsight::class)
        ->and($insights->firstWhere('key', 'overdue-tasks')->count)->toBe(2);
});

it('flags closed-won opportunities this week via stage custom field', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $opportunity = Opportunity::factory()->for($team)->create();
    $stageFieldId = (string) Str::ulid();
    CustomField::query()->create([
        'id' => $stageFieldId,
        'code' => 'stage',
        'name' => 'Stage',
        'type' => 'select',
        'entity_type' => Opportunity::class,
        'tenant_id' => $team->id,
        'active' => true,
        'system_defined' => true,
    ]);
    CustomFieldValue::query()->create([
        'id' => (string) Str::ulid(),
        'entity_type' => Opportunity::class,
        'entity_id' => $opportunity->id,
        'custom_field_id' => $stageFieldId,
        'tenant_id' => $team->id,
        'string_value' => 'Won',
    ]);

    $insights = (new CrmInsightsService)->forTeam($team);
    expect($insights->firstWhere('key', 'recent-wins'))->not->toBeNull();
});

it('reports pipeline value across opportunities', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    $opportunities = Opportunity::factory()->for($team)->count(3)->create();
    $amountFieldId = (string) Str::ulid();
    CustomField::query()->create([
        'id' => $amountFieldId,
        'code' => 'amount',
        'name' => 'Amount',
        'type' => 'number',
        'entity_type' => Opportunity::class,
        'tenant_id' => $team->id,
        'active' => true,
        'system_defined' => true,
    ]);
    foreach ($opportunities as $i => $opportunity) {
        CustomFieldValue::query()->create([
            'id' => (string) Str::ulid(),
            'entity_type' => Opportunity::class,
            'entity_id' => $opportunity->id,
            'custom_field_id' => $amountFieldId,
            'tenant_id' => $team->id,
            'integer_value' => 5000 * ($i + 1),
        ]);
    }

    $insights = (new CrmInsightsService)->forTeam($team);
    $pipeline = $insights->firstWhere('key', 'pipeline-value');
    expect($pipeline)->not->toBeNull()
        ->and($pipeline->count)->toBe(30000);
});
