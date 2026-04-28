<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Models\PersonalAccessToken;
use Laravel\Passport\AccessToken as PassportAccessToken;
use Laravel\Sanctum\Exceptions\MissingAbilityException;

trait ChecksTokenAbility
{
    /**
     * @throws MissingAbilityException
     */
    protected function ensureTokenCan(string $ability): void
    {
        $user = auth()->user();
        $token = $user?->currentAccessToken();

        if ($token instanceof PassportAccessToken) {
            throw_unless($token->can($ability), MissingAbilityException::class, [$ability]);

            return;
        }

        if (! $token instanceof PersonalAccessToken || ! $token->getKey()) {
            return;
        }

        throw_unless($token->can($ability), MissingAbilityException::class, [$ability]);
    }
}
