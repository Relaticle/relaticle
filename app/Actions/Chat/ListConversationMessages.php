<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ListConversationMessages
{
    /**
     * @return array<int, array{role: string, content: string, pending_actions: array<int, mixed>}>
     */
    public function execute(User $user, string $conversationId): array
    {
        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->getKey())
            ->oldest()
            ->get(['role', 'content', 'tool_results'])
            ->map(fn (object $msg): array => [
                'role' => $msg->role,
                'content' => $msg->content ?? '',
                'pending_actions' => $this->extractPendingActions($msg->tool_results),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function extractPendingActions(?string $toolResults): array
    {
        if ($toolResults === null) {
            return [];
        }

        /** @var array<string, array<string, mixed>>|null $results */
        $results = json_decode($toolResults, true);

        if (! is_array($results)) {
            return [];
        }

        $actions = [];

        foreach ($results as $toolResult) {
            if (! isset($toolResult['result'])) {
                continue;
            }

            /** @var array<string, mixed>|null $parsed */
            $parsed = json_decode($toolResult['result'], true);

            if (is_array($parsed) && ($parsed['type'] ?? null) === 'pending_action') {
                $pendingActionId = $parsed['pending_action_id'] ?? null;

                $status = $pendingActionId
                    ? DB::table('pending_actions')->where('id', $pendingActionId)->value('status')
                    : 'expired';

                $parsed['status'] = $status ?? 'expired';
                $actions[] = $parsed;
            }
        }

        return $actions;
    }
}
