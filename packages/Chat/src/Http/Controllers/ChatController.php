<?php

declare(strict_types=1);

namespace Relaticle\Chat\Http\Controllers;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\ConversationStore;
use Relaticle\Chat\Actions\DeleteConversation;
use Relaticle\Chat\Actions\ListConversations;
use Relaticle\Chat\Actions\RenameConversation;
use Relaticle\Chat\Enums\AiModel;
use Relaticle\Chat\Jobs\ProcessChatMessage;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\Chat\Services\AiModelResolver;
use Relaticle\Chat\Services\CreditService;
use Relaticle\Chat\Support\TitleSanitizer;

final readonly class ChatController
{
    public function __construct(
        private CreditService $creditService,
        private AiModelResolver $modelResolver,
        private ConversationStore $conversationStore,
    ) {}

    public function send(Request $request, ?string $conversation = null): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'model' => ['nullable', 'string', Rule::enum(AiModel::class)],
            'conversation_id' => ['nullable', 'string', 'uuid'],
            'mentions' => ['nullable', 'array', 'max:15'],
            'mentions.*.type' => ['required_with:mentions', 'string', 'in:company,people,opportunity,task,note'],
            'mentions.*.id' => ['required_with:mentions', 'string', 'ulid'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam;

        $conversation ??= $validated['conversation_id'] ?? null;

        if ($conversation !== null) {
            $existing = DB::table('agent_conversations')->where('id', $conversation)->first();

            if ($existing !== null) {
                abort_if(
                    $existing->user_id !== (string) $user->getKey()
                        || ($existing->team_id !== null && $existing->team_id !== $team->getKey()),
                    403
                );
            }
        }

        if (! $this->creditService->reserveCredit($team)) {
            $balance = AiCreditBalance::query()
                ->where('team_id', $team->getKey())
                ->first();

            return response()->json([
                'error' => 'credits_exhausted',
                'message' => 'You have used all your AI credits for this billing period.',
                'reset_at' => $balance?->period_ends_at?->toIso8601String(),
                'upgrade_url' => url('/app/billing'),
            ], 402);
        }

        if ($conversation === null) {
            $conversation = $this->conversationStore->storeConversation(
                (string) $user->getKey(),
                TitleSanitizer::clean($validated['message']),
            );
        } else {
            DB::table('agent_conversations')->insertOrIgnore([
                'id' => $conversation,
                'user_id' => (string) $user->getKey(),
                'team_id' => $team->getKey(),
                'title' => TitleSanitizer::clean($validated['message']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::transaction(function () use ($conversation, $user, $team): void {
            $row = DB::table('agent_conversations')
                ->where('id', $conversation)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                return;
            }

            abort_if($row->user_id !== (string) $user->getKey(), 403);

            if ($row->team_id !== null) {
                return;
            }

            DB::table('agent_conversations')
                ->where('id', $conversation)
                ->update(['team_id' => $team->getKey(), 'updated_at' => now()]);
        });

        $resolved = $this->modelResolver->resolve($user, $validated['model'] ?? null, $validated['message']);

        $resolvedMentions = $this->resolveMentions(
            $validated['mentions'] ?? [],
            $team,
        );

        dispatch(new ProcessChatMessage(
            user: $user,
            team: $team,
            message: $validated['message'],
            conversationId: $conversation,
            resolved: $resolved,
            mentions: $resolvedMentions,
        ));

        return response()->json([
            'status' => 'processing',
            'conversation_id' => $conversation,
        ]);
    }

    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'string', 'uuid'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam;

        abort_if($team === null, 403);

        $existing = DB::table('agent_conversations')->where('id', $validated['conversation_id'])->first();

        if ($existing !== null) {
            abort_if(
                $existing->user_id !== (string) $user->getKey()
                    || ($existing->team_id !== null && $existing->team_id !== $team->getKey()),
                403
            );

            return response()->json(['conversation_id' => $validated['conversation_id']]);
        }

        DB::table('agent_conversations')->insert([
            'id' => $validated['conversation_id'],
            'user_id' => (string) $user->getKey(),
            'team_id' => $team->getKey(),
            'title' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['conversation_id' => $validated['conversation_id']]);
    }

    public function cancel(Request $request, string $conversationId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Cache::put(
            "chat:cancel:{$conversationId}",
            (string) $user->getKey(),
            now()->addMinutes(5),
        );

        return response()->json(['cancelled' => true]);
    }

    public function mentions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $search = $this->escapeLikeWildcards($validated['q']);
        $limit = 5;

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam;

        $results = collect();

        $results = $results->merge(
            Company::query()
                ->whereBelongsTo($team)
                ->where('name', 'ilike', "%{$search}%")
                ->orderByRaw('CASE WHEN name ilike ? THEN 0 ELSE 1 END', ["{$search}%"])
                ->orderByRaw('LENGTH(name) ASC')
                ->orderBy('name')
                ->limit($limit)
                ->with('team')
                ->get(['id', 'name', 'team_id'])
                ->filter(fn (Company $r): bool => $user->can('view', $r))
                ->values()
                ->map(fn (Company $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'company'])
        );

        $results = $results->merge(
            People::query()
                ->whereBelongsTo($team)
                ->where('name', 'ilike', "%{$search}%")
                ->orderByRaw('CASE WHEN name ilike ? THEN 0 ELSE 1 END', ["{$search}%"])
                ->orderByRaw('LENGTH(name) ASC')
                ->orderBy('name')
                ->limit($limit)
                ->with('team')
                ->get(['id', 'name', 'team_id'])
                ->filter(fn (People $r): bool => $user->can('view', $r))
                ->values()
                ->map(fn (People $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'people'])
        );

        $results = $results->merge(
            Opportunity::query()
                ->whereBelongsTo($team)
                ->where('name', 'ilike', "%{$search}%")
                ->orderByRaw('CASE WHEN name ilike ? THEN 0 ELSE 1 END', ["{$search}%"])
                ->orderByRaw('LENGTH(name) ASC')
                ->orderBy('name')
                ->limit($limit)
                ->with('team')
                ->get(['id', 'name', 'team_id'])
                ->filter(fn (Opportunity $r): bool => $user->can('view', $r))
                ->values()
                ->map(fn (Opportunity $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'opportunity'])
        );

        $results = $results->merge(
            Task::query()
                ->whereBelongsTo($team)
                ->where('title', 'ilike', "%{$search}%")
                ->orderByRaw('CASE WHEN title ilike ? THEN 0 ELSE 1 END', ["{$search}%"])
                ->orderByRaw('LENGTH(title) ASC')
                ->orderBy('title')
                ->limit($limit)
                ->with('team')
                ->get(['id', 'title', 'team_id'])
                ->filter(fn (Task $r): bool => $user->can('view', $r))
                ->values()
                ->map(fn (Task $r): array => ['id' => $r->id, 'name' => $r->title, 'type' => 'task'])
        );

        $results = $results->merge(
            Note::query()
                ->whereBelongsTo($team)
                ->where('title', 'ilike', "%{$search}%")
                ->orderByRaw('CASE WHEN title ilike ? THEN 0 ELSE 1 END', ["{$search}%"])
                ->orderByRaw('LENGTH(title) ASC')
                ->orderBy('title')
                ->limit($limit)
                ->with('team')
                ->get(['id', 'title', 'team_id'])
                ->filter(fn (Note $r): bool => $user->can('view', $r))
                ->values()
                ->map(fn (Note $r): array => ['id' => $r->id, 'name' => $r->title, 'type' => 'note'])
        );

        return response()->json(['data' => $results->take(15)->values()]);
    }

    public function conversations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new ListConversations)->execute($user),
        ]);
    }

    public function destroyConversation(Request $request, string $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! (new DeleteConversation)->execute($user, $conversation)) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        return response()->json(['success' => true]);
    }

    public function rename(Request $request, string $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $title = (new RenameConversation)->execute(
                $user,
                $conversationId,
                $validated['title'],
            );
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->json(['title' => $title]);
    }

    private function escapeLikeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param  list<array{type: string, id: string}>  $mentions
     * @return list<array{type: string, id: string, label: string}>
     */
    private function resolveMentions(array $mentions, Team $team): array
    {
        if ($mentions === []) {
            return [];
        }

        $resolved = [];

        foreach ($mentions as $mention) {
            $record = match ($mention['type']) {
                'company' => Company::query()->whereBelongsTo($team)->find($mention['id']),
                'people' => People::query()->whereBelongsTo($team)->find($mention['id']),
                'opportunity' => Opportunity::query()->whereBelongsTo($team)->find($mention['id']),
                'task' => Task::query()->whereBelongsTo($team)->find($mention['id']),
                'note' => Note::query()->whereBelongsTo($team)->find($mention['id']),
                default => null,
            };

            if ($record === null) {
                continue;
            }

            $resolved[] = [
                'type' => $mention['type'],
                'id' => $mention['id'],
                'label' => $record instanceof Task || $record instanceof Note ? $record->title : $record->name,
            ];
        }

        return $resolved;
    }
}
