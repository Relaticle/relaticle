<?php

declare(strict_types=1);

use App\Models\Team;
use Illuminate\Support\Facades\Artisan;
use Relaticle\Chat\Models\AiCreditBalance;

it('resets expired balances to the free-tier allowance during the scheduled reset', function (): void {
    config()->set('chat.credits.free', 100);

    $expired = now()->subDay();

    $team = Team::factory()->create();

    AiCreditBalance::query()
        ->where('team_id', $team->getKey())
        ->update([
            'credits_remaining' => 5,
            'credits_used' => 95,
            'period_starts_at' => $expired->copy()->subMonth(),
            'period_ends_at' => $expired,
        ]);

    Artisan::call('chat:reset-credits');

    expect($team->fresh()->aiCreditBalance->credits_remaining)->toBe(100);
});

it('skips teams whose period has not yet expired', function (): void {
    $team = Team::factory()->create();
    AiCreditBalance::query()
        ->where('team_id', $team->getKey())
        ->update([
            'credits_remaining' => 42,
            'credits_used' => 100,
            'period_starts_at' => now()->startOfMonth(),
            'period_ends_at' => now()->endOfMonth(),
        ]);

    Artisan::call('chat:reset-credits');

    expect($team->fresh()->aiCreditBalance->credits_remaining)->toBe(42);
});
