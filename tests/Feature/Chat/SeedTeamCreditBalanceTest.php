<?php

declare(strict_types=1);

use App\Actions\Chat\SeedTeamCreditBalance;
use App\Models\Team;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\Chat\Models\AiCreditTransaction;

mutates(SeedTeamCreditBalance::class);

it('creates a free-plan balance for a team that has none', function (): void {
    $team = Team::factory()->create();
    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();

    expect(AiCreditBalance::query()->where('team_id', $team->getKey())->exists())->toBeFalse();

    app(SeedTeamCreditBalance::class)->handle($team);

    $balance = AiCreditBalance::query()->where('team_id', $team->getKey())->firstOrFail();
    expect($balance->credits_remaining)->toBe((int) config('chat.credits.free'))
        ->and($balance->credits_used)->toBe(0)
        ->and($balance->period_ends_at->isAfter(now()))->toBeTrue();
});

it('is idempotent — calling twice does not double-grant or duplicate the audit row', function (): void {
    $team = Team::factory()->create();
    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();
    AiCreditTransaction::query()->where('team_id', $team->getKey())->delete();

    $action = app(SeedTeamCreditBalance::class);
    $action->handle($team);
    $action->handle($team);

    expect(AiCreditBalance::query()->where('team_id', $team->getKey())->count())->toBe(1);

    $audits = AiCreditTransaction::query()
        ->where('team_id', $team->getKey())
        ->where('type', AiCreditType::Adjustment)
        ->get();

    expect($audits)->toHaveCount(1)
        ->and($audits->first()->metadata['action'])->toBe('seed_initial_balance');
});

it('respects the plan passed in for paid-tier seeding', function (): void {
    config()->set('chat.credits.pro', 2_500);
    $team = Team::factory()->create();
    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();

    app(SeedTeamCreditBalance::class)->handle($team, plan: 'pro');

    $balance = AiCreditBalance::query()->where('team_id', $team->getKey())->firstOrFail();
    expect($balance->credits_remaining)->toBe(2_500);
});
