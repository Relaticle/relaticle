<?php

declare(strict_types=1);

use App\Http\Middleware\CheckScheduledDeletion;
use App\Livewire\App\Profile\ScheduledDeletionInterstitial;
use App\Models\User;
use Filament\Facades\Filament;

mutates(CheckScheduledDeletion::class, ScheduledDeletionInterstitial::class);

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

test('interstitial redirects non-scheduled user to home', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);
    Filament::setTenant($user->personalTeam());

    livewire(ScheduledDeletionInterstitial::class)
        ->assertRedirect(Filament::getHomeUrl());
});

test('user can cancel deletion from interstitial', function () {
    $user = User::factory()->withPersonalTeam()->scheduledForDeletion()->create();

    $this->actingAs($user);
    Filament::setTenant($user->personalTeam());

    livewire(ScheduledDeletionInterstitial::class)
        ->call('cancelDeletion')
        ->assertRedirect(Filament::getHomeUrl());

    expect($user->refresh()->scheduled_deletion_at)->toBeNull();
});

test('user can logout from interstitial', function () {
    $user = User::factory()->withPersonalTeam()->scheduledForDeletion()->create();

    $this->actingAs($user);

    livewire(ScheduledDeletionInterstitial::class)
        ->call('logout')
        ->assertRedirect(Filament::getLoginUrl());
});
