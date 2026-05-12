<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Models\McpToolInvocationLog;
use App\Models\User;

trait LogsToolInvocation
{
    /**
     * Record an MCP tool invocation. Call at the start of handle() and pass
     * the returned start-time float back to completeLog() when done.
     */
    protected function startLog(string $toolName): float
    {
        return microtime(true);
    }

    /**
     * Write the completed invocation log entry.
     */
    protected function completeLog(string $toolName, float $startedAt): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $team = $user->currentTeam;

        if ($team === null) {
            return;
        }

        McpToolInvocationLog::query()->create([
            'team_id' => $team->getKey(),
            'user_id' => $user->getKey(),
            'tool_name' => $toolName,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }
}
