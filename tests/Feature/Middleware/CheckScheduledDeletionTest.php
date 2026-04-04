<?php

declare(strict_types=1);

use App\Http\Middleware\CheckScheduledDeletion;
use App\Livewire\App\Profile\ScheduledDeletionInterstitial;
use App\Models\User;

mutates(CheckScheduledDeletion::class);

test('user with scheduled deletion is redirected to interstitial from panel', function () {
    $user = User::factory()->withPersonalTeam()->scheduledForDeletion()->create();

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect(route('scheduled-deletion'));
});

test('user without scheduled deletion is not redirected to interstitial', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $response = $this->actingAs($user)->get('/app');

    expect($response->headers->get('Location'))->not->toBe(route('scheduled-deletion'));
});

test('interstitial component renders for user with scheduled deletion', function () {
    $user = User::factory()->withPersonalTeam()->scheduledForDeletion()->create();

    $this->actingAs($user);

    livewire(ScheduledDeletionInterstitial::class)
        ->assertSuccessful()
        ->assertSee('Account Scheduled for Deletion');
});
