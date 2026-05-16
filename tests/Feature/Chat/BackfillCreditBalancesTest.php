<?php

declare(strict_types=1);

use App\Actions\Chat\SeedTeamCreditBalance;
use App\Enums\Plan;
use App\Models\Team;
use Relaticle\Chat\Models\AiCreditBalance;

it('backfills credit balances for teams that have none', function (): void {
    $t1 = Team::factory()->create();
    $t2 = Team::factory()->create();
    $t3 = Team::factory()->create();
    AiCreditBalance::query()->whereIn('team_id', [$t1->getKey(), $t2->getKey(), $t3->getKey()])->delete();

    $missingCount = Team::query()->whereDoesntHave('aiCreditBalance')->count();
    expect($missingCount)->toBeGreaterThanOrEqual(3);

    $action = app(SeedTeamCreditBalance::class);
    Team::query()
        ->whereDoesntHave('aiCreditBalance')
        ->chunkById(200, function ($teams) use ($action): void {
            foreach ($teams as $team) {
                $action->handle($team);
            }
        });

    expect(Team::query()->whereDoesntHave('aiCreditBalance')->count())->toBe(0);
    expect($t1->fresh()->aiCreditBalance)->not->toBeNull()
        ->and($t2->fresh()->aiCreditBalance)->not->toBeNull()
        ->and($t3->fresh()->aiCreditBalance)->not->toBeNull();
});

it('backfills a team that lacks a plan value without crashing', function (): void {
    $team = Team::factory()->create();

    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();

    // Simulate a team whose `plan` attribute is null (as would happen when the
    // backfill migration runs before the `plan` column migration has applied).
    $team->forceFill(['plan' => null]);

    $balance = resolve(SeedTeamCreditBalance::class)->handle($team);

    expect($balance->credits_remaining)->toBe(Plan::default()->credits());
});
