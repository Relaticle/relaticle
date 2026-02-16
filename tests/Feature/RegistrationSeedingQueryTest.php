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

    expect($counter->count())->toBeGreaterThan(0);
    expect($counter->count())->toBeLessThan(200);
});
