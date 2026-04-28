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

        // The User model uses Sanctum's HasApiTokens trait, so currentAccessToken()
        // is typed as PersonalAccessToken|null upstream. At runtime, Passport
        // hydrates an AccessToken instance through the same accessor — widen the
        // inferred type so both branches are reachable for static analysis.
        /** @var PersonalAccessToken|PassportAccessToken|object|null $token */
        $token = $user?->currentAccessToken();

        if ($token instanceof PassportAccessToken) {
            /** @var bool $can */
            $can = $token->can($ability);
            throw_unless($can, MissingAbilityException::class, [$ability]);

            return;
        }

        if (! $token instanceof PersonalAccessToken || ! $token->getKey()) {
            return;
        }

        /** @var bool $can */
        $can = $token->can($ability);
        throw_unless($can, MissingAbilityException::class, [$ability]);
    }
}
