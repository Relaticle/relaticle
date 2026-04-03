<?php

declare(strict_types=1);

namespace App\Listeners\Email;

use App\Data\SubscriberData;
use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\CreateSubscriberJob;
use App\Jobs\Email\UpdateSubscriberJob;
use App\Models\User;
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
        /** @var User $user */
        $user = $event->user;

        if ($user->hasVerifiedEmail() && config('mailcoach-sdk.enabled_subscribers_sync', false)) {
            $signupSourceTag = $user->socialAccounts()->exists()
                ? SubscriberTagEnum::SIGNUP_SOURCE_GOOGLE->value
                : SubscriberTagEnum::SIGNUP_SOURCE_ORGANIC->value;

            $tags = [SubscriberTagEnum::VERIFIED->value, $signupSourceTag];

            $subscriber = retry(10, fn (): mixed => Mailcoach::findByEmail(config('mailcoach-sdk.subscribers_list_id'), $user->email), fn (int $attempt): int => $attempt * 100 * random_int(1, 15));

            if ($subscriber) {
                dispatch(new UpdateSubscriberJob(SubscriberData::from([
                    'email' => $user->email,
                    'tags' => $tags,
                    'user_id' => (string) $user->id,
                ])));
            }

            dispatch(new CreateSubscriberJob(SubscriberData::from([
                'email' => $user->email,
                'first_name' => $user->name,
                'last_name' => $user->name,
                'tags' => $tags,
                'skip_confirmation' => true,
                'user_id' => (string) $user->id,
            ])));
        }
    }
}
