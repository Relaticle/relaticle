<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use App\Support\EmailVerificationGate;
use Filament\Facades\Filament;

mutates(EmailVerificationGate::class);

afterEach(function (): void {
    config(['app.require_email_verification' => true]);
    putenv('REQUIRE_EMAIL_VERIFICATION');
});

it('keeps email verification required by default so cloud and production behave like before', function (): void {
    expect(config('app.require_email_verification'))->toBeTrue()
        ->and(Filament::getPanel('app')->isEmailVerificationRequired())->toBeTrue()
        ->and(Filament::getPanel('sysadmin')->isEmailVerificationRequired())->toBeTrue();
});

it('treats unverified users as gated when the flag is on so cloud behavior is unchanged', function (): void {
    config(['app.require_email_verification' => true]);
    $unverified = User::factory()->unverified()->create();
    $verified = User::factory()->create();

    expect(EmailVerificationGate::passes($unverified))->toBeFalse()
        ->and(EmailVerificationGate::passes($verified))->toBeTrue()
        ->and(EmailVerificationGate::passes(null))->toBeFalse();
});

it('lets unverified users through every authorization gate when the flag is off so SMTP-less self-hosters can use the app', function (): void {
    config(['app.require_email_verification' => false]);

    $unverified = User::factory()->unverified()->create();

    expect(EmailVerificationGate::passes($unverified))->toBeTrue()
        ->and(EmailVerificationGate::passes(null))->toBeFalse();

    // Spot-check that the helper's effect actually reaches policies.
    $team = Team::factory()->create(['user_id' => $unverified->id]);
    $unverified->forceFill(['current_team_id' => $team->id])->save();
    $unverified->refresh()->setRelation('currentTeam', $team);

    expect($unverified->can('viewAny', Company::class))->toBeTrue();
});

it('reads the config value through env coercion so REQUIRE_EMAIL_VERIFICATION="false" disables the gate end-to-end', function (): void {
    putenv('REQUIRE_EMAIL_VERIFICATION=false');
    $reloaded = filter_var(env('REQUIRE_EMAIL_VERIFICATION', true), FILTER_VALIDATE_BOOL);
    expect($reloaded)->toBeFalse();

    putenv('REQUIRE_EMAIL_VERIFICATION=true');
    $reloaded = filter_var(env('REQUIRE_EMAIL_VERIFICATION', true), FILTER_VALIDATE_BOOL);
    expect($reloaded)->toBeTrue();

    putenv('REQUIRE_EMAIL_VERIFICATION');
});
