<?php

declare(strict_types=1);

use App\Features\Documentation;
use App\Features\OnboardSeed;
use App\Features\SocialAuth;
use App\Filament\Pages\CreateTeam;
use App\Models\Company;
use App\Models\User;
use Laravel\Pennant\Feature;

mutates(OnboardSeed::class, SocialAuth::class, Documentation::class);

describe('OnboardSeed', function (): void {
    it('seeds demo data when feature is active', function (): void {
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

        expect(Company::where('team_id', $team->id)->count())->toBeGreaterThan(0);
    });

    it('skips demo data when feature is inactive', function (): void {
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
});

describe('SocialAuth', function (): void {
    it('registers social auth routes when feature is active', function (): void {
        Feature::define(SocialAuth::class, true);

        $this->get(route('auth.socialite.redirect', 'google'))
            ->assertRedirect();
    });

    it('returns 404 for social auth routes when feature is inactive', function (): void {
        Feature::deactivate(SocialAuth::class);

        $this->get(route('auth.socialite.redirect', 'google'))
            ->assertNotFound();
    });

    it('returns 404 for social auth callback when feature is inactive', function (): void {
        Feature::deactivate(SocialAuth::class);

        $this->get(route('auth.socialite.callback', 'google'))
            ->assertNotFound();
    });
});

describe('Documentation', function (): void {
    it('serves documentation pages when feature is active', function (): void {
        Feature::define(Documentation::class, true);

        $this->get('/docs')
            ->assertOk();
    });

    it('returns 404 for documentation pages when feature is inactive', function (): void {
        Feature::deactivate(Documentation::class);

        $this->get('/docs')
            ->assertNotFound();
    });
});
