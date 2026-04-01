<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Ai\Agents\CrmAssistant;
use App\Enums\AiCreditType;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use App\Services\AI\AiModelResolver;
use App\Services\AI\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $response->then(function (StreamedAgentResponse $streamedResponse) use ($user, $team): void {
            $usage = $streamedResponse->usage;
            $meta = $streamedResponse->meta;
            $toolCallCount = $streamedResponse->toolCalls->count();

            $this->creditService->deduct(
                team: $team,
                user: $user,
                type: AiCreditType::Chat,
                model: $meta->model ?? 'unknown',
                inputTokens: $usage->promptTokens,
                outputTokens: $usage->completionTokens,
                toolCallsCount: $toolCallCount,
                conversationId: $streamedResponse->conversationId,
            );
        });

        return $response->toResponse($request);
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

        $conversations = DB::table('agent_conversations')
            ->where('user_id', $user->getKey())
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json(['data' => $conversations]);
    }

    public function destroyConversation(Request $request, string $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deleted = DB::table('agent_conversations')
            ->where('id', $conversation)
            ->where('user_id', $user->getKey())
            ->delete();

        if ($deleted === 0) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation)
            ->delete();

        return response()->json(['success' => true]);
    }
}
