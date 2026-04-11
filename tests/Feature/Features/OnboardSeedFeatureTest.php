<?php

declare(strict_types=1);

use App\Features\OnboardSeed;
use App\Filament\Pages\CreateTeam;
use App\Models\Company;
use App\Models\User;
use Laravel\Pennant\Feature;

mutates(OnboardSeed::class);

it('seeds demo data when onboard seed feature is active', function (): void {
    Feature::define(OnboardSeed::class, true);

    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'name' => 'Seed Enabled Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    expect(Company::where('team_id', $team->id)->count())->toBe(4);
});

it('skips demo data when onboard seed feature is inactive', function (): void {
    Feature::define(OnboardSeed::class, false);

    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'name' => 'Seed Disabled Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    expect(Company::where('team_id', $team->id)->count())->toBe(0);
});
