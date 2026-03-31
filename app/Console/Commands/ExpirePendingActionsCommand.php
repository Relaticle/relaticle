<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\PendingActionService;
use Illuminate\Console\Command;

final class ExpirePendingActionsCommand extends Command
{
    protected $signature = 'chat:expire-pending-actions';

    protected $description = 'Mark expired pending chat actions as expired';

    public function handle(PendingActionService $service): int
    {
        $count = $service->expireStale();

        $this->comment("Expired {$count} pending action(s).");

        return self::SUCCESS;
    }
}
