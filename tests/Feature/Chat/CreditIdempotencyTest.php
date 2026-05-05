<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Models\AiCreditTransaction;
use Relaticle\Chat\Services\CreditService;

it('does not double-charge when settle is called twice with the same idempotency key', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $service = app(CreditService::class);
    $service->resetPeriod($team, 100);

    DB::table('agent_conversations')->insert([
        'id' => 'conv_1',
        'user_id' => $user->getKey(),
        'team_id' => $team->getKey(),
        'title' => 'Credit idempotency',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $args = [
        'team' => $team,
        'user' => $user,
        'type' => AiCreditType::Chat,
        'model' => 'claude-sonnet-4',
        'inputTokens' => 100,
        'outputTokens' => 200,
        'toolCallsCount' => 1,
        'conversationId' => 'conv_1',
        'idempotencyKey' => 'response_abc123',
    ];

    $service->settleReservation(...$args);
    $service->settleReservation(...$args);

    expect(AiCreditTransaction::query()->count())->toBe(1);
});
