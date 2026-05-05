<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\Chat\Models\AiCreditTransaction;

final readonly class CreditService
{
    public function hasCredits(Team $team): bool
    {
        if ((bool) config('ai.unlimited_credits', false)) {
            return true;
        }

        return $this->getBalance($team) > 0;
    }

    /**
     * Atomically reserve one credit up-front. Prevents concurrent requests from
     * bypassing a non-atomic credit gate when the team has a small balance.
     */
    public function reserveCredit(Team $team): bool
    {
        if ((bool) config('ai.unlimited_credits', false)) {
            return true;
        }

        return DB::transaction(function () use ($team): bool {
            $balance = AiCreditBalance::query()
                ->where('team_id', $team->getKey())
                ->lockForUpdate()
                ->first();

            if (! $balance instanceof AiCreditBalance || $balance->credits_remaining < 1) {
                return false;
            }

            $balance->update([
                'credits_remaining' => $balance->credits_remaining - 1,
                'credits_used' => $balance->credits_used + 1,
            ]);

            return true;
        });
    }

    /**
     * Refund a previously reserved credit (e.g. when the downstream job fails).
     *
     * Pass an `$idempotencyToken` to ensure the refund only happens once across
     * cancel and failure paths for the same logical job invocation.
     */
    public function refundReservation(Team $team, int $credits = 1, ?string $idempotencyToken = null): void
    {
        if ((bool) config('ai.unlimited_credits', false)) {
            return;
        }

        if ($idempotencyToken !== null) {
            $cacheKey = "chat:refund-lock:{$team->getKey()}:{$idempotencyToken}";
            if (! Cache::add($cacheKey, '1', now()->addHour())) {
                return;
            }
        }

        DB::transaction(function () use ($team, $credits): void {
            $balance = AiCreditBalance::query()
                ->where('team_id', $team->getKey())
                ->lockForUpdate()
                ->first();

            if (! $balance instanceof AiCreditBalance) {
                return;
            }

            $balance->update([
                'credits_remaining' => $balance->credits_remaining + $credits,
                'credits_used' => max($balance->credits_used - $credits, 0),
            ]);
        });
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
        ?string $idempotencyKey = null,
    ): void {
        if ((bool) config('ai.unlimited_credits', false)) {
            return;
        }

        if ($idempotencyKey !== null && AiCreditTransaction::query()
            ->where('team_id', $team->getKey())
            ->where('idempotency_key', $idempotencyKey)
            ->exists()
        ) {
            return;
        }

        $creditsCharged = $this->calculateCredits($model, $toolCallsCount);

        DB::transaction(function () use ($team, $user, $type, $model, $inputTokens, $outputTokens, $creditsCharged, $toolCallsCount, $conversationId, $idempotencyKey): void {
            $balance = AiCreditBalance::query()
                ->where('team_id', $team->getKey())
                ->lockForUpdate()
                ->first();

            if (! $balance instanceof AiCreditBalance) {
                $balance = AiCreditBalance::query()->create([
                    'team_id' => $team->getKey(),
                    'credits_remaining' => 0,
                    'credits_used' => 0,
                    'period_starts_at' => now()->startOfMonth(),
                    'period_ends_at' => now()->endOfMonth(),
                ]);
            }

            $balance->update([
                'credits_remaining' => max($balance->credits_remaining - $creditsCharged, 0),
                'credits_used' => $balance->credits_used + $creditsCharged,
            ]);

            AiCreditTransaction::query()->create([
                'team_id' => $team->getKey(),
                'user_id' => $user->getKey(),
                'conversation_id' => $conversationId,
                'idempotency_key' => $idempotencyKey,
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

    /**
     * Settle a previously reserved credit by applying the difference between
     * the real cost of the request and the 1 credit already reserved.
     */
    public function settleReservation(
        Team $team,
        User $user,
        AiCreditType $type,
        string $model,
        int $inputTokens,
        int $outputTokens,
        int $toolCallsCount = 0,
        ?string $conversationId = null,
        int $reservedCredits = 1,
        ?string $idempotencyKey = null,
    ): void {
        if ((bool) config('ai.unlimited_credits', false)) {
            return;
        }

        if ($idempotencyKey !== null && AiCreditTransaction::query()
            ->where('team_id', $team->getKey())
            ->where('idempotency_key', $idempotencyKey)
            ->exists()
        ) {
            return;
        }

        $creditsCharged = $this->calculateCredits($model, $toolCallsCount);
        $adjustment = $creditsCharged - $reservedCredits;

        DB::transaction(function () use ($team, $user, $type, $model, $inputTokens, $outputTokens, $creditsCharged, $toolCallsCount, $conversationId, $adjustment, $idempotencyKey): void {
            if ($adjustment !== 0) {
                $balance = AiCreditBalance::query()
                    ->where('team_id', $team->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $balance instanceof AiCreditBalance) {
                    $balance = AiCreditBalance::query()->create([
                        'team_id' => $team->getKey(),
                        'credits_remaining' => 0,
                        'credits_used' => 0,
                        'period_starts_at' => now()->startOfMonth(),
                        'period_ends_at' => now()->endOfMonth(),
                    ]);
                }

                if ($adjustment > 0) {
                    $balance->update([
                        'credits_remaining' => max($balance->credits_remaining - $adjustment, 0),
                        'credits_used' => $balance->credits_used + $adjustment,
                    ]);
                } else {
                    $refund = abs($adjustment);
                    $balance->update([
                        'credits_remaining' => $balance->credits_remaining + $refund,
                        'credits_used' => max($balance->credits_used - $refund, 0),
                    ]);
                }
            }

            AiCreditTransaction::query()->create([
                'team_id' => $team->getKey(),
                'user_id' => $user->getKey(),
                'conversation_id' => $conversationId,
                'idempotency_key' => $idempotencyKey,
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
