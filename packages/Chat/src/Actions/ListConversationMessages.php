<?php

declare(strict_types=1);

namespace Relaticle\Chat\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Support\MarkdownRenderer;

final readonly class ListConversationMessages
{
    public function __construct(
        private MarkdownRenderer $markdown = new MarkdownRenderer,
    ) {}

    /**
     * @return array<int, array{role: string, content: string, pending_actions: array<int, mixed>}>
     */
    public function execute(User $user, string $conversationId): array
    {
        return DB::table('agent_conversation_messages as m')
            ->join('agent_conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('m.conversation_id', $conversationId)
            ->where('m.user_id', $user->getKey())
            ->where('c.team_id', $user->current_team_id)
            ->oldest('m.created_at')
            ->get(['m.role', 'm.content', 'm.tool_results'])
            ->map(fn (object $msg): array => [
                'role' => $msg->role,
                'content' => $msg->role === 'assistant'
                    ? $this->markdown->render($msg->content ?? '')
                    : ($msg->content ?? ''),
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
            $parsed = json_decode((string) $toolResult['result'], true);

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
