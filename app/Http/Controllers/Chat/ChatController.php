<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\DeleteConversation;
use App\Actions\Chat\ListConversations;
use App\Ai\Agents\CrmAssistant;
use App\Enums\AiCreditType;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Scopes\TeamScope;
use App\Models\Task;
use App\Models\User;
use App\Services\AI\AiModelResolver;
use App\Services\AI\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ChatController
{
    public function __construct(
        private CreditService $creditService,
        private AiModelResolver $modelResolver,
    ) {}

    public function send(Request $request, ?string $conversation = null): Response
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'model' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam;

        $this->applyTenantScopes();

        if (! $this->creditService->hasCredits($team)) {
            return response()->json([
                'error' => 'credits_exhausted',
                'message' => 'You have used all your AI credits for this billing period.',
            ], 402);
        }

        $agent = resolve(CrmAssistant::class);

        if ($conversation !== null) {
            $agent->continue($conversation, as: $user);
        } else {
            $agent->forUser($user);
        }

        $resolved = $this->modelResolver->resolve($user, $validated['model'] ?? null);

        $response = $agent->stream(
            prompt: $validated['message'],
            provider: $resolved['provider'],
            model: $resolved['model'],
        );

        $resolvedConversationId = null;

        $response->then(function (StreamedAgentResponse $streamedResponse) use ($user, $team, &$resolvedConversationId): void {
            $resolvedConversationId = $streamedResponse->conversationId;

            $this->creditService->deduct(
                team: $team,
                user: $user,
                type: AiCreditType::Chat,
                model: $streamedResponse->meta->model ?? 'unknown',
                inputTokens: $streamedResponse->usage->promptTokens,
                outputTokens: $streamedResponse->usage->completionTokens,
                toolCallsCount: $streamedResponse->toolCalls->count(),
                conversationId: $streamedResponse->conversationId,
            );
        });

        return response()->stream(function () use ($response, &$resolvedConversationId): void {
            foreach ($response as $event) {
                echo "data: {$event}\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            if ($resolvedConversationId) {
                $payload = json_encode(['type' => 'conversation_id', 'id' => $resolvedConversationId]);
                echo "data: {$payload}\n\n";
            }

            echo "data: [DONE]\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, headers: ['Content-Type' => 'text/event-stream']);
    }

    public function mentions(Request $request): JsonResponse
    {
        $this->applyTenantScopes();

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

    private function applyTenantScopes(): void
    {
        Company::addGlobalScope(new TeamScope);
        People::addGlobalScope(new TeamScope);
        Opportunity::addGlobalScope(new TeamScope);
        Task::addGlobalScope(new TeamScope);
        Note::addGlobalScope(new TeamScope);
    }
}
