<?php

declare(strict_types=1);

namespace Relaticle\Chat\Http\Controllers;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Relaticle\Chat\Actions\DeleteConversation;
use Relaticle\Chat\Actions\FindConversation;
use Relaticle\Chat\Actions\ListConversations;
use Relaticle\Chat\Jobs\ProcessChatMessage;
use Relaticle\Chat\Services\AiModelResolver;
use Relaticle\Chat\Services\CreditService;

final readonly class ChatController
{
    public function __construct(
        private CreditService $creditService,
        private AiModelResolver $modelResolver,
    ) {}

    public function send(Request $request, ?string $conversation = null): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'model' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam;

        if ($conversation !== null) {
            $found = (new FindConversation)->execute($user, $conversation);

            if ($found === null) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }
        }

        if (! $this->creditService->hasCredits($team)) {
            return response()->json([
                'error' => 'credits_exhausted',
                'message' => 'You have used all your AI credits for this billing period.',
            ], 402);
        }

        $resolved = $this->modelResolver->resolve($user, $validated['model'] ?? null);

        ProcessChatMessage::dispatch(
            user: $user,
            team: $team,
            message: $validated['message'],
            conversationId: $conversation,
            resolved: $resolved,
        );

        return response()->json(['status' => 'processing']);
    }

    public function mentions(Request $request): JsonResponse
    {
        $query = $request->string('q');

        if ($query->length() < 2) {
            return response()->json(['data' => []]);
        }

        $search = (string) $query;
        $limit = 5;

        $results = collect();

        $results = $results->merge(
            Company::query()->where('name', 'ilike', "%{$search}%")->limit($limit)->get(['id', 'name'])->map(fn (Company $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'company'])
        );

        $results = $results->merge(
            People::query()->where('name', 'ilike', "%{$search}%")->limit($limit)->get(['id', 'name'])->map(fn (People $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'people'])
        );

        $results = $results->merge(
            Opportunity::query()->where('name', 'ilike', "%{$search}%")->limit($limit)->get(['id', 'name'])->map(fn (Opportunity $r): array => ['id' => $r->id, 'name' => $r->name, 'type' => 'opportunity'])
        );

        $results = $results->merge(
            Task::query()->where('title', 'ilike', "%{$search}%")->limit($limit)->get(['id', 'title'])->map(fn (Task $r): array => ['id' => $r->id, 'name' => $r->title, 'type' => 'task'])
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
}
