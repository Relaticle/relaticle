<?php

declare(strict_types=1);

namespace App\Jobs\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachSdk\Facades\Mailcoach;

final class AddSubscriberTagsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        private readonly string $subscriberUuid,
        private readonly array $tags,
    ) {}

    public function handle(): void
    {
        Mailcoach::post("subscribers/{$this->subscriberUuid}/tags", ['tags' => $this->tags]);
    }

    public function uniqueId(): string
    {
        return $this->subscriberUuid.':add:'.implode(',', $this->tags);
    }

    /** @return array<ThrottlesExceptionsWithRedis> */
    public function middleware(): array
    {
        return [new ThrottlesExceptionsWithRedis(1, 1)->backoff(1)->report()];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHour();
    }
}
