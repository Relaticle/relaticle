<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\Chat\Models\AiCreditTransaction;

final readonly class CreditService
{
    public function hasCredits(Team $team): bool
    {
        return $this->getBalance($team) > 0;
    }

    public function getBalance(Team $team): int
    {
        $balance = AiCreditBalance::query()
            ->where('team_id', $team->getKey())
            ->first();

        if (! $balance instanceof AiCreditBalance) {
            return 0;
        }

        return $balance->credits_remaining;
    }

    public function deduct(
        Team $team,
        User $user,
        AiCreditType $type,
        string $model,
        int $inputTokens,
        int $outputTokens,
        int $toolCallsCount = 0,
        ?string $conversationId = null,
    ): void {
        $creditsCharged = $this->calculateCredits($model, $toolCallsCount);

        DB::transaction(function () use ($team, $user, $type, $model, $inputTokens, $outputTokens, $creditsCharged, $toolCallsCount, $conversationId): void {
            $balance = AiCreditBalance::query()
                ->where('team_id', $team->getKey())
                ->lockForUpdate()
                ->first();

            if (! $balance instanceof AiCreditBalance) {
                return;
            }

            $balance->update([
                'credits_remaining' => max($balance->credits_remaining - $creditsCharged, 0),
                'credits_used' => $balance->credits_used + $creditsCharged,
            ]);

            AiCreditTransaction::query()->create([
                'team_id' => $team->getKey(),
                'user_id' => $user->getKey(),
                'conversation_id' => $conversationId,
                'type' => $type,
                'model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'credits_charged' => $creditsCharged,
                'metadata' => ['tool_calls_count' => $toolCallsCount],
                'created_at' => now(),
            ]);
        });
    }

    public function calculateCredits(string $model, int $toolCallsCount): int
    {
        /** @var array<string, float> $multipliers */
        $multipliers = config('chat.model_multipliers', []);
        $multiplier = $multipliers[$model] ?? 1.0;
        $toolBonus = (float) config('chat.tool_call_credit_bonus', 0.5);

        $raw = (1 * $multiplier) + ($toolCallsCount * $toolBonus);

        return max(1, (int) ceil($raw));
    }

    public function resetPeriod(Team $team, int $creditAllowance): void
    {
        AiCreditBalance::query()->updateOrCreate(
            ['team_id' => $team->getKey()],
            [
                'credits_remaining' => $creditAllowance,
                'credits_used' => 0,
                'period_starts_at' => now()->startOfMonth(),
                'period_ends_at' => now()->endOfMonth(),
            ],
        );
    }
}
