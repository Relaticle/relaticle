<?php

declare(strict_types=1);

use App\Jobs\Email\AddSubscriberTagsJob;
use Spatie\MailcoachSdk\Facades\Mailcoach;

mutates(AddSubscriberTagsJob::class);

beforeEach(function () {
    config(['mailcoach-sdk.api_token' => 'fake-token', 'mailcoach-sdk.endpoint' => 'https://fake.mailcoach.test']);
});

test('it calls the Mailcoach add tags endpoint with the subscriber UUID', function () {
    Mailcoach::shouldReceive('post')
        ->once()
        ->with('subscribers/test-uuid-123/tags', ['tags' => ['has-crm-data']])
        ->andReturnNull();

    (new AddSubscriberTagsJob('test-uuid-123', ['has-crm-data']))->handle();
});

test('it sends multiple tags in a single call', function () {
    Mailcoach::shouldReceive('post')
        ->once()
        ->with('subscribers/test-uuid-456/tags', ['tags' => ['active-7d', 'has-crm-data']])
        ->andReturnNull();

    (new AddSubscriberTagsJob('test-uuid-456', ['active-7d', 'has-crm-data']))->handle();
});
