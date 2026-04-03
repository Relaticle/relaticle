<?php

declare(strict_types=1);

namespace App\Jobs\Email;

use App\Data\SubscriberData;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachSdk\Facades\Mailcoach;
use Spatie\MailcoachSdk\Resources\Subscriber;
use Throwable;

final class CreateSubscriberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly SubscriberData $data) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $subscriber = retry(10, fn (): Subscriber => Mailcoach::createSubscriber(
            config('mailcoach-sdk.subscribers_list_id'),
            $this->data->except('user_id')->toArray(),
        ), fn (int $attempt): int => $attempt * 100 * random_int(1, 15));

        if ($this->data->user_id) {
            User::query()
                ->where('id', $this->data->user_id)
                ->whereNull('mailcoach_subscriber_uuid')
                ->update(['mailcoach_subscriber_uuid' => $subscriber->uuid]);
        }
    }
}
