<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\AddSubscriberTagsJob;
use App\Jobs\Email\RemoveSubscriberTagsJob;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class SyncSubscriberRecencyTagsCommand extends Command
{
    protected $signature = 'subscribers:sync-recency-tags';

    protected $description = 'Sync time-decay recency tags to Mailcoach for users whose bucket changed';

    public function handle(): int
    {
        if (! config('mailcoach-sdk.enabled_subscribers_sync', false)) {
            $this->info('Mailcoach subscriber sync is disabled.');

            return self::SUCCESS;
        }

        $synced = 0;

        User::query()
            ->whereNotNull('mailcoach_subscriber_uuid')
            ->select(['id', 'last_login_at', 'mailcoach_subscriber_uuid', 'subscriber_recency_bucket'])
            ->chunkById(200, function (Collection $users) use (&$synced): void {
                /** @var User $user */
                foreach ($users as $user) {
                    $newBucket = $this->computeBucket($user);
                    $oldBucket = $user->subscriber_recency_bucket;

                    if ($newBucket === $oldBucket) {
                        continue;
                    }

                    if ($oldBucket !== null) {
                        dispatch(new RemoveSubscriberTagsJob(
                            $user->mailcoach_subscriber_uuid,
                            [$oldBucket],
                        ));
                    }

                    if ($newBucket !== null) {
                        dispatch(new AddSubscriberTagsJob(
                            $user->mailcoach_subscriber_uuid,
                            [$newBucket],
                        ));
                    }

                    $user->forceFill(['subscriber_recency_bucket' => $newBucket])->saveQuietly();
                    $synced++;
                }
            });

        $this->info("Synced recency tags for {$synced} users.");

        return self::SUCCESS;
    }

    private function computeBucket(User $user): ?string
    {
        if ($user->last_login_at === null) {
            return SubscriberTagEnum::DORMANT->value;
        }

        $daysSinceLogin = (int) $user->last_login_at->diffInDays(now());

        return match (true) {
            $daysSinceLogin <= 7 => SubscriberTagEnum::ACTIVE_7D->value,
            $daysSinceLogin <= 30 => SubscriberTagEnum::ACTIVE_30D->value,
            $daysSinceLogin > 60 => SubscriberTagEnum::DORMANT->value,
            default => null, // 31-60 days: transition window, no tag
        };
    }
}
