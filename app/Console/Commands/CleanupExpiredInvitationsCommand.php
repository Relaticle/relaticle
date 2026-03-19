<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TeamInvitation;
use Illuminate\Console\Command;

final class CleanupExpiredInvitationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'invitations:cleanup
                            {--days=30 : Delete invitations expired more than this many days ago}';

    /**
     * @var string
     */
    protected $description = 'Delete team invitations that have been expired for a specified number of days';

    public function handle(): void
    {
        $days = (int) $this->option('days');

        $cutoff = now()->subDays($days);

        $deleted = TeamInvitation::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where('expires_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNull('expires_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->delete();

        $this->info("Purged {$deleted} expired invitation(s).");
    }
}
