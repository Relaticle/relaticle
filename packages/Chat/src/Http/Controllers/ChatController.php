<?php

declare(strict_types=1);

namespace Relaticle\Chat\Http\Controllers;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\ConversationStore;
use Relaticle\Chat\Actions\DeleteConversation;
use Relaticle\Chat\Actions\FindConversation;
use Relaticle\Chat\Actions\ListConversations;
use Relaticle\Chat\Enums\AiModel;
use Relaticle\Chat\Jobs\ProcessChatMessage;
use Relaticle\Chat\Services\AiModelResolver;
use Relaticle\Chat\Services\CreditService;

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
        ]);

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam;

        if ($conversation !== null) {
            $found = (new FindConversation)->execute($user, $conversation);

            if (! $found instanceof \stdClass) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }
        }

        if (! $this->creditService->reserveCredit($team)) {
            return response()->json([
                'error' => 'credits_exhausted',
                'message' => 'You have used all your AI credits for this billing period.',
            ], 402);
        }

        $conversation ??= $this->conversationStore->storeConversation(
            (string) $user->getKey(),
            Str::limit($validated['message'], 100, preserveWords: true),
        );

        $resolved = $this->modelResolver->resolve($user, $validated['model'] ?? null);

        dispatch(new ProcessChatMessage(
            user: $user,
            team: $team,
            message: $validated['message'],
            conversationId: $conversation,
            resolved: $resolved,
        ));

        return response()->json([
            'status' => 'processing',
            'conversation_id' => $conversation,
        ]);
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
                ->limit($limit)
                ->get(['id', 'name'])
                ->map(fn (Company $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'company'])
        );

        $results = $results->merge(
            People::query()
                ->whereBelongsTo($team)
                ->where('name', 'ilike', "%{$search}%")
                ->limit($limit)
                ->get(['id', 'name'])
                ->map(fn (People $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'people'])
        );

        $results = $results->merge(
            Opportunity::query()
                ->whereBelongsTo($team)
                ->where('name', 'ilike', "%{$search}%")
                ->limit($limit)
                ->get(['id', 'name'])
                ->map(fn (Opportunity $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'opportunity'])
        );

        $results = $results->merge(
            Task::query()
                ->whereBelongsTo($team)
                ->where('title', 'ilike', "%{$search}%")
                ->limit($limit)
                ->get(['id', 'title'])
                ->map(fn (Task $r): array => ['id' => $r->id, 'name' => $r->title, 'type' => 'task'])
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

    private function escapeLikeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
