<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Ai\Agents\CrmAssistant;
use App\Enums\AiCreditType;
use App\Models\User;
use App\Services\AI\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Symfony\Component\HttpFoundation\Response;

final class ChatController
{
    public function __construct(
        private readonly CreditService $creditService,
    ) {}

    public function send(Request $request, ?string $conversation = null): Response
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
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

        $agent = app(CrmAssistant::class);

        if ($conversation !== null) {
            $agent->continue($conversation, as: $user);
        } else {
            $agent->forUser($user);
        }

        $response = $agent->stream($validated['message']);

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

    public function conversations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $conversations = DB::table('agent_conversations')
            ->where('user_id', $user->getKey())
            ->orderByDesc('updated_at')
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
