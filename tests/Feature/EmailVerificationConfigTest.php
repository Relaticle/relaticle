<?php

declare(strict_types=1);

use Filament\Facades\Filament;

it('keeps email verification required by default so cloud and production behave like before', function (): void {
    expect(config('app.require_email_verification'))->toBeTrue()
        ->and(Filament::getPanel('app')->isEmailVerificationRequired())->toBeTrue()
        ->and(Filament::getPanel('sysadmin')->isEmailVerificationRequired())->toBeTrue();
});

it('disables the verification gate on both panels when self-hosters set REQUIRE_EMAIL_VERIFICATION=false', function (): void {
    config(['app.require_email_verification' => false]);

    Filament::getPanel('app')->emailVerification(isRequired: config('app.require_email_verification'));
    Filament::getPanel('sysadmin')->emailVerification(isRequired: config('app.require_email_verification'));

    expect(Filament::getPanel('app')->isEmailVerificationRequired())->toBeFalse()
        ->and(Filament::getPanel('sysadmin')->isEmailVerificationRequired())->toBeFalse();
});

it('coerces string env values like "false" to a real boolean so .env files behave intuitively', function (): void {
    config(['app.require_email_verification' => filter_var('false', FILTER_VALIDATE_BOOL)]);
    expect(config('app.require_email_verification'))->toBeFalse();

    config(['app.require_email_verification' => filter_var('true', FILTER_VALIDATE_BOOL)]);
    expect(config('app.require_email_verification'))->toBeTrue();
});
