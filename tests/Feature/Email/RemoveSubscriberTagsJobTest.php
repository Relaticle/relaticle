<?php

declare(strict_types=1);

use App\Jobs\Email\RemoveSubscriberTagsJob;
use Spatie\MailcoachSdk\Facades\Mailcoach;

mutates(RemoveSubscriberTagsJob::class);

beforeEach(function () {
    config(['mailcoach-sdk.api_token' => 'fake-token', 'mailcoach-sdk.endpoint' => 'https://fake.mailcoach.test']);
});

test('it calls the Mailcoach delete tags endpoint with the subscriber UUID', function () {
    Mailcoach::shouldReceive('delete')
        ->once()
        ->with('subscribers/test-uuid-123/tags', ['tags' => ['dormant']])
        ->andReturnNull();

    (new RemoveSubscriberTagsJob('test-uuid-123', ['dormant']))->handle();
});
