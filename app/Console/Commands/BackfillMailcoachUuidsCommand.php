<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use Spatie\MailcoachSdk\Facades\Mailcoach;
use Spatie\MailcoachSdk\Resources\Subscriber;

final class BackfillMailcoachUuidsCommand extends Command
{
    protected $signature = 'subscribers:backfill-uuids';

    protected $description = 'Backfill mailcoach_subscriber_uuid for existing users from the Mailcoach API';

    public function handle(): int
    {
        $users = User::query()
            ->whereNull('mailcoach_subscriber_uuid')
            ->whereNotNull('email_verified_at')
            ->select(['id', 'email'])
            ->get();

        $this->info("Found {$users->count()} users without Mailcoach UUID.");

        $bar = $this->output->createProgressBar($users->count());
        $matched = 0;
        $notFound = 0;

        foreach ($users as $user) {
            try {
                $subscriber = Mailcoach::findByEmail(
                    config('mailcoach-sdk.subscribers_list_id'),
                    $user->email,
                );

                if ($subscriber instanceof Subscriber) {
                    $user->forceFill(['mailcoach_subscriber_uuid' => $subscriber->uuid])->saveQuietly();
                    $matched++;
                } else {
                    $notFound++;
                }
            } catch (\Throwable $e) {
                $this->warn("Failed for {$user->email}: {$e->getMessage()}");
            }

            $bar->advance();
            Sleep::usleep(100_000); // 100ms delay to respect API rate limits
        }

        $bar->finish();
        $this->newLine();
        $this->info("Matched: {$matched}, Not found: {$notFound}");

        return self::SUCCESS;
    }
}
