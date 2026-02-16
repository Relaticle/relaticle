<?php

declare(strict_types=1);

use App\Providers\HealthServiceProvider;
use Spatie\Health\Facades\Health;

mutates(HealthServiceProvider::class);

it('does not register health checks when disabled', function () {
    Health::clearChecks();

    config()->set('app.health_checks_enabled', false);

    $provider = new HealthServiceProvider(app());
    $provider->register();

    expect(Health::registeredChecks())->toBeEmpty();
});

it('registers health checks when enabled', function () {
    Health::clearChecks();

    config()->set('app.health_checks_enabled', true);

    $provider = new HealthServiceProvider(app());
    $provider->register();

    expect(Health::registeredChecks())->not->toBeEmpty()
        ->and(Health::registeredChecks())->toHaveCount(15);
});
