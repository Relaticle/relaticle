<?php

declare(strict_types=1);

use App\Features\SocialAuth;
use Laravel\Pennant\Feature;

mutates(SocialAuth::class);

it('registers social auth routes when feature is active', function (): void {
    Feature::define(SocialAuth::class, true);

    $this->get(route('auth.socialite.redirect', 'google'))
        ->assertRedirect();
});

it('returns 404 for social auth routes when feature is inactive', function (): void {
    Feature::activate(SocialAuth::class, false);

    $this->get('/auth/redirect/google')
        ->assertNotFound();
});

it('returns 404 for social auth callback when feature is inactive', function (): void {
    Feature::activate(SocialAuth::class, false);

    $this->get('/auth/callback/google')
        ->assertNotFound();
});
