<?php

declare(strict_types=1);

namespace Relaticle\Chat\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\Chat\Services\CreditService;

final class ResetCreditsCommand extends Command
{
    protected $signature = 'chat:reset-credits';

    protected $description = 'Reset AI credits for teams whose billing period has ended';

    public function handle(CreditService $service): int
    {
        $defaultAllowance = (int) config('chat.credits.free', 100);

        $expired = AiCreditBalance::query()
            ->where('period_ends_at', '<', now())
            ->get();

        $resetCount = 0;

        foreach ($expired as $balance) {
            /** @var Team|null $team */
            $team = Team::query()->find($balance->team_id);

            if ($team === null) {
                continue;
            }

            $service->resetPeriod($team, $defaultAllowance);
            $resetCount++;
        }

        $this->comment("Reset credits for {$resetCount} team(s).");

        return self::SUCCESS;
    }
}
