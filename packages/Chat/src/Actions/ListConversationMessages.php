<?php

declare(strict_types=1);

namespace Relaticle\Chat\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Support\MarkdownRenderer;
use stdClass;

final readonly class ListConversationMessages
{
    public function __construct(
        private MarkdownRenderer $markdown = new MarkdownRenderer,
    ) {}

    /**
     * @return array<int, array{id: string, role: string, content: string, pending_actions: array<int, mixed>}>
     */
    public function execute(User $user, string $conversationId): array
    {
        $messages = DB::table('agent_conversation_messages as m')
            ->join('agent_conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('m.conversation_id', $conversationId)
            ->where('m.user_id', $user->getKey())
            ->where('c.team_id', $user->current_team_id)
            ->oldest('m.created_at')
            ->get(['m.id', 'm.role', 'm.content', 'm.tool_results']);

        $pendingIds = $this->collectPendingActionIds($messages);

        /** @var array<string, string> $statuses */
        $statuses = $pendingIds === []
            ? []
            : DB::table('pending_actions')
                ->whereIn('id', $pendingIds)
                ->pluck('status', 'id')
                ->all();

        return $messages->map(fn (object $msg): array => [
            'id' => (string) $msg->id,
            'role' => (string) $msg->role,
            'content' => $msg->role === 'assistant'
                ? $this->markdown->render((string) ($msg->content ?? ''))
                : (string) ($msg->content ?? ''),
            'pending_actions' => $this->extractPendingActions(
                $msg->tool_results === null ? null : (string) $msg->tool_results,
                $statuses,
            ),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, stdClass>  $messages
     * @return list<string>
     */
    private function collectPendingActionIds(Collection $messages): array
    {
        $ids = [];

        foreach ($messages as $msg) {
            $rawToolResults = $msg->tool_results ?? null;
            $parsed = json_decode((string) ($rawToolResults ?? 'null'), true);

            if (! is_array($parsed)) {
                continue;
            }

            foreach ($parsed as $toolResult) {
                if (! is_array($toolResult)) {
                    continue;
                }
                if (! isset($toolResult['result'])) {
                    continue;
                }
                $inner = json_decode((string) $toolResult['result'], true);

                if (is_array($inner) && ($inner['type'] ?? null) === 'pending_action' && isset($inner['pending_action_id'])) {
                    $ids[] = (string) $inner['pending_action_id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, string>  $statuses
     * @return array<int, mixed>
     */
    private function extractPendingActions(?string $toolResults, array $statuses): array
    {
        if ($toolResults === null) {
            return [];
        }

        $parsed = json_decode($toolResults, true);

        if (! is_array($parsed)) {
            return [];
        }

        $actions = [];

        foreach ($parsed as $toolResult) {
            if (! is_array($toolResult)) {
                continue;
            }
            if (! isset($toolResult['result'])) {
                continue;
            }
            $inner = json_decode((string) $toolResult['result'], true);

            if (is_array($inner) && ($inner['type'] ?? null) === 'pending_action') {
                $pendingId = (string) ($inner['pending_action_id'] ?? '');
                $inner['status'] = $statuses[$pendingId] ?? 'expired';
                $actions[] = $inner;
            }
        }

        return $actions;
    }
}
