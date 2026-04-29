<?php

declare(strict_types=1);

use App\Console\Commands\RefreshDemoAccountCommand;
use App\Models\Company;
use App\Models\User;

mutates(RefreshDemoAccountCommand::class);

it('creates the demo account on first run', function (): void {
    $this->artisan('app:refresh-demo-account')->assertSuccessful();

    $user = User::query()->where('email', 'demo@relaticle.com')->firstOrFail();

    expect($user->two_factor_secret)->toBeNull();
    expect($user->personalTeam())->not->toBeNull();
    expect(Company::query()->where('team_id', $user->personalTeam()->getKey())->count())->toBeGreaterThan(0);
});

it('is idempotent and resets demo data on re-run', function (): void {
    $this->artisan('app:refresh-demo-account')->assertSuccessful();
    $this->artisan('app:refresh-demo-account')->assertSuccessful();

    expect(User::query()->where('email', 'demo@relaticle.com')->count())->toBe(1);
});

it('refuses to run in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('app:refresh-demo-account')
        ->expectsOutputToContain('Refusing to run in production')
        ->assertFailed();

    expect(User::query()->where('email', 'demo@relaticle.com')->exists())->toBeFalse();
});
