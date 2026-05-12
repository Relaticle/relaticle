<?php

declare(strict_types=1);

use App\Models\User;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\Chat\Services\CreditService;

mutates(CreditService::class);

it('lazy-creates a free balance when hasCredits is called on a team with no row', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();

    $service = app(CreditService::class);

    expect($service->hasCredits($team))->toBeTrue()
        ->and(AiCreditBalance::query()->where('team_id', $team->getKey())->exists())->toBeTrue();
});

it('lazy-init grants the configured free allowance, not zero', function (): void {
    config()->set('chat.credits.free', 137);
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();

    app(CreditService::class)->hasCredits($team);

    $balance = AiCreditBalance::query()->where('team_id', $team->getKey())->firstOrFail();
    expect($balance->credits_remaining)->toBe(137);
});

it('lazy-creates and successfully reserves a credit when reserveCredit is called on a team with no row', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    AiCreditBalance::query()->where('team_id', $team->getKey())->delete();

    config()->set('chat.credits.free', 5);

    $service = app(CreditService::class);

    expect($service->reserveCredit($team))->toBeTrue();

    $balance = AiCreditBalance::query()->where('team_id', $team->getKey())->firstOrFail();
    expect($balance->credits_remaining)->toBe(4)
        ->and($balance->credits_used)->toBe(1);
});
