<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Notifications\DeletionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\DeletesUsers;

final class PurgeScheduledDeletionsCommand extends Command
{
    protected $signature = 'app:purge-scheduled-deletions';

    protected $description = 'Permanently delete users and teams past their scheduled deletion date, and send day-25 reminders';

    public function handle(DeletesUsers $deletesUsers, DeletesTeams $deletesTeams): int
    {
        $this->purgeExpiredUsers($deletesUsers);
        $this->purgeExpiredTeams($deletesTeams);
        $this->sendReminders();

        return self::SUCCESS;
    }

    private function purgeExpiredUsers(DeletesUsers $deletesUsers): void
    {
        $users = User::query()
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now())
            ->get();

        $users->each(function (User $user) use ($deletesUsers): void {
            DB::transaction(fn () => $deletesUsers->delete($user));

            Log::info('Purged user account', ['user_id' => $user->id, 'email' => $user->email]);
            $this->info("Purged user: {$user->email}");
        });

        $this->info("Purged {$users->count()} user(s).");
    }

    private function purgeExpiredTeams(DeletesTeams $deletesTeams): void
    {
        $teams = Team::query()
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now())
            ->get();

        $teams->each(function (Team $team) use ($deletesTeams): void {
            DB::transaction(fn () => $deletesTeams->delete($team));

            Log::info('Purged team', ['team_id' => $team->id, 'name' => $team->name]);
            $this->info("Purged team: {$team->name}");
        });

        $this->info("Purged {$teams->count()} team(s).");
    }

    private function sendReminders(): void
    {
        $reminderStart = now()->addDays(5)->startOfDay();
        $reminderEnd = now()->addDays(5)->endOfDay();

        User::query()
            ->whereNotNull('scheduled_deletion_at')
            ->whereBetween('scheduled_deletion_at', [$reminderStart, $reminderEnd])
            ->each(function (User $user): void {
                $user->notify(new DeletionReminderNotification($user->name, $user->scheduled_deletion_at, 'user'));
            });

        Team::query()
            ->whereNotNull('scheduled_deletion_at')
            ->whereBetween('scheduled_deletion_at', [$reminderStart, $reminderEnd])
            ->with('owner', 'users')
            ->each(function (Team $team): void {
                $team->allUsers()->each(function (User $member) use ($team): void {
                    $member->notify(new DeletionReminderNotification($team->name, $team->scheduled_deletion_at, 'team'));
                });
            });
    }
}
