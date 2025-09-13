<?php

declare(strict_types=1);

namespace App\Listeners\Email;

use App\Data\SubscriberData;
use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\CreateSubscriberJob;
use App\Jobs\Email\UpdateSubscriberJob;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\MailcoachSdk\Facades\Mailcoach;
use Throwable;

final class NewSubscriberListener implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(Verified $event): void
    {
        if ($event->user->hasVerifiedEmail()) {
            $subscriber = retry(10, function () use ($event) {
                return Mailcoach::findByEmail(config('mailcoach-sdk.subscribers_list_id'), $event->user->email);
            }, function ($attempt) {
                return $attempt * 100 * mt_rand(1, 15);
            });

            if ($subscriber) {
                UpdateSubscriberJob::dispatch(
                    SubscriberData::from([
                        'email' => $event->user->email,
                        'tags' => [SubscriberTagEnum::VERIFIED->value],
                    ])
                );
            }

            CreateSubscriberJob::dispatch(
                SubscriberData::from([
                    'email' => $event->user->email,
                    'first_name' => $event->user->name,
                    'last_name' => $event->user->name,
                    'tags' => [SubscriberTagEnum::VERIFIED->value],
                    'skip_confirmation' => true,
                ])
            );
        }
    }
}
