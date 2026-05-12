<?php

declare(strict_types=1);

use App\Models\Team;
use Illuminate\Support\Facades\Artisan;
use Relaticle\Chat\Models\AiCreditBalance;

it('resets each plan to its own allowance during the scheduled reset', function (): void {
    config()->set('chat.credits.free', 100);
    config()->set('chat.credits.starter', 500);
    config()->set('chat.credits.pro', 2_000);
    config()->set('chat.credits.enterprise', 10_000);

    $expired = now()->subDay();

    $free = Team::factory()->create();
    $starter = Team::factory()->create();
    $pro = Team::factory()->create();
    $ent = Team::factory()->create();

    foreach (
        [
            [$free, 'free', 5],
            [$starter, 'starter', 5],
            [$pro, 'pro', 5],
            [$ent, 'enterprise', 5],
        ] as [$team, $plan, $remaining]
    ) {
        AiCreditBalance::query()
            ->where('team_id', $team->getKey())
            ->update([
                'plan' => $plan,
                'credits_remaining' => $remaining,
                'credits_used' => 95,
                'period_starts_at' => $expired->copy()->subMonth(),
                'period_ends_at' => $expired,
            ]);
    }

    Artisan::call('chat:reset-credits');

    expect($free->fresh()->aiCreditBalance->credits_remaining)->toBe(100)
        ->and($starter->fresh()->aiCreditBalance->credits_remaining)->toBe(500)
        ->and($pro->fresh()->aiCreditBalance->credits_remaining)->toBe(2_000)
        ->and($ent->fresh()->aiCreditBalance->credits_remaining)->toBe(10_000);
});

it('skips teams whose period has not yet expired', function (): void {
    $team = Team::factory()->create();
    AiCreditBalance::query()
        ->where('team_id', $team->getKey())
        ->update([
            'plan' => 'pro',
            'credits_remaining' => 42,
            'credits_used' => 100,
            'period_starts_at' => now()->startOfMonth(),
            'period_ends_at' => now()->endOfMonth(),
        ]);

    Artisan::call('chat:reset-credits');

    expect($team->fresh()->aiCreditBalance->credits_remaining)->toBe(42);
});
