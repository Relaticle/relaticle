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

        // currentAccessToken() returns null when:
        //   1. The request authenticated via $this->actingAs($user) in tests (no token).
        //   2. The request authenticated via session/web guard (no API token).
        // Both are acceptable bypasses today: feature tests rely on (1), and the
        // MCP route is only reachable via the auth:sanctum,api guard, which never
        // produces case (2). If you ADD a non-API guard to routes/ai.php, revisit.
        /** @var PersonalAccessToken|PassportAccessToken|object|null $token */
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
