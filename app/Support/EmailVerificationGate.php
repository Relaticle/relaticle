<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail;

/**
 * Single source of truth for "is this user allowed past the verified-email gate?"
 *
 * When `app.require_email_verification` is false (self-host without SMTP), every
 * verification check in policies, panel guards, and menu gates short-circuits to
 * true. Cloud / production deployments leave the config at true and behavior is
 * unchanged.
 */
final class EmailVerificationGate
{
    public static function passes(?MustVerifyEmail $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (! config('app.require_email_verification')) {
            return true;
        }

        return $user->hasVerifiedEmail();
    }
}
