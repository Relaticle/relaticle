<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamCreated;
use Tests\Helpers\QueryCounter;

it('tracks query count for team creation seeding', function () {
    Event::fake([TeamCreated::class]);

    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->personalTeam();

    Event::assertDispatched(TeamCreated::class);

    $user->switchTeam($team);

    $counter = new QueryCounter;
    $counter->start();

    app(\App\Listeners\CreateTeamCustomFields::class)->handle(new TeamCreated($team));

    $counter->stop();

    $counter->dump();

    $existsQueries = $counter->findRepeated('select exists');
    dump("EXISTS check queries: {$existsQueries['count']}");

    $optionUpdates = $counter->findRepeated('update');
    dump("UPDATE queries: {$optionUpdates['count']}");

    $cfvInserts = $counter->findRepeated('custom_field_values');
    dump("Custom field value queries: {$cfvInserts['count']}");

    dump("Total queries: {$counter->count()}");

    expect($counter->count())->toBeGreaterThan(0);
});
