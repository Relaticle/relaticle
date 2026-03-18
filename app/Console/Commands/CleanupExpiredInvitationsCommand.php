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

        $deleted = TeamInvitation::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Purged {$deleted} expired invitation(s).");
    }
}
