<?php

declare(strict_types=1);

namespace App\Jobs\Email;

use App\Data\SubscriberData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachSdk\Facades\Mailcoach;
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
        retry(10, function (): void {
            Mailcoach::createSubscriber(config('mailcoach-sdk.subscribers_list_id'), $this->data->toArray());
        }, fn (int $attempt): int => $attempt * 100 * random_int(1, 15));
    }
}
