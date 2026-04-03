<?php

declare(strict_types=1);

use App\Console\Commands\SyncSubscriberRecencyTagsCommand;
use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\AddSubscriberTagsJob;
use App\Jobs\Email\RemoveSubscriberTagsJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

mutates(SyncSubscriberRecencyTagsCommand::class);

beforeEach(function () {
    Queue::fake([AddSubscriberTagsJob::class, RemoveSubscriberTagsJob::class]);
    config()->set('mailcoach-sdk.enabled_subscribers_sync', true);
});

test('user who logged in today gets active-7d tag', function () {
    $this->travelTo(now());

    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-active',
        'last_login_at' => now()->subHours(2),
        'subscriber_recency_bucket' => null,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertPushed(AddSubscriberTagsJob::class, function (AddSubscriberTagsJob $job) {
        return invade($job)->subscriberUuid === 'uuid-active'
            && invade($job)->tags === [SubscriberTagEnum::ACTIVE_7D->value];
    });
});

test('user who logged in 15 days ago gets active-30d tag', function () {
    $this->travelTo(now());

    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-semi',
        'last_login_at' => now()->subDays(15),
        'subscriber_recency_bucket' => null,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertPushed(AddSubscriberTagsJob::class, function (AddSubscriberTagsJob $job) {
        return invade($job)->subscriberUuid === 'uuid-semi'
            && invade($job)->tags === [SubscriberTagEnum::ACTIVE_30D->value];
    });
});

test('user who never logged in gets dormant tag', function () {
    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-dormant',
        'last_login_at' => null,
        'subscriber_recency_bucket' => null,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertPushed(AddSubscriberTagsJob::class, function (AddSubscriberTagsJob $job) {
        return invade($job)->subscriberUuid === 'uuid-dormant'
            && invade($job)->tags === [SubscriberTagEnum::DORMANT->value];
    });
});

test('user whose bucket has not changed is skipped', function () {
    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-stable',
        'last_login_at' => now()->subDays(2),
        'subscriber_recency_bucket' => SubscriberTagEnum::ACTIVE_7D->value,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertNotPushed(AddSubscriberTagsJob::class);
    Queue::assertNotPushed(RemoveSubscriberTagsJob::class);
});

test('bucket transition removes old tag and adds new tag', function () {
    $this->travelTo(now());

    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-transition',
        'last_login_at' => now()->subDays(10),
        'subscriber_recency_bucket' => SubscriberTagEnum::ACTIVE_7D->value,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertPushed(RemoveSubscriberTagsJob::class, function (RemoveSubscriberTagsJob $job) {
        return invade($job)->subscriberUuid === 'uuid-transition'
            && invade($job)->tags === [SubscriberTagEnum::ACTIVE_7D->value];
    });

    Queue::assertPushed(AddSubscriberTagsJob::class, function (AddSubscriberTagsJob $job) {
        return invade($job)->subscriberUuid === 'uuid-transition'
            && invade($job)->tags === [SubscriberTagEnum::ACTIVE_30D->value];
    });
});

test('user without mailcoach uuid is skipped', function () {
    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => null,
        'last_login_at' => now(),
        'subscriber_recency_bucket' => null,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertNotPushed(AddSubscriberTagsJob::class);
});

test('subscriber_recency_bucket is updated after sync', function () {
    $this->travelTo(now());

    $user = User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-update',
        'last_login_at' => now()->subDays(2),
        'subscriber_recency_bucket' => null,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    expect($user->refresh()->subscriber_recency_bucket)->toBe(SubscriberTagEnum::ACTIVE_7D->value);
});

test('user in 30-60 day transition window gets no recency tag', function () {
    $this->travelTo(now());

    User::factory()->withTeam()->create([
        'mailcoach_subscriber_uuid' => 'uuid-limbo',
        'last_login_at' => now()->subDays(45),
        'subscriber_recency_bucket' => SubscriberTagEnum::ACTIVE_30D->value,
    ]);

    $this->artisan(SyncSubscriberRecencyTagsCommand::class)->assertSuccessful();

    Queue::assertPushed(RemoveSubscriberTagsJob::class, function (RemoveSubscriberTagsJob $job) {
        return invade($job)->tags === [SubscriberTagEnum::ACTIVE_30D->value];
    });

    Queue::assertNotPushed(AddSubscriberTagsJob::class);
});
