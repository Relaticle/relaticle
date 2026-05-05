<?php

declare(strict_types=1);

namespace Relaticle\Chat\Jobs;

use App\Models\Team;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Relaticle\Chat\Agents\CrmAssistant;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Events\ChatStreamFailed;
use Relaticle\Chat\Events\ConversationResolved;
use Relaticle\Chat\Events\FollowUpsSuggested;
use Relaticle\Chat\Models\AiCreditTransaction;
use Relaticle\Chat\Services\CreditService;
use Relaticle\Chat\Services\FollowUpService;
use Relaticle\Chat\Support\ChatTelemetry;
use Throwable;

#[Timeout(120)]
#[Tries(1)]
final class ProcessChatMessage implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{provider: string|null, model: string|null}  $resolved
     * @param  list<array{type: string, id: string, label: string}>  $mentions
     */
    public function __construct(
        private readonly User $user,
        private readonly Team $team,
        private readonly string $message,
        private readonly string $conversationId,
        private readonly array $resolved,
        public readonly array $mentions = [],
    ) {
        $this->onQueue('chat');
    }

    public function handle(CreditService $creditService): void
    {
        $this->bindAuth();

        ChatTelemetry::tagCurrentScope(
            $this->conversationId,
            (string) $this->team->getKey(),
            $this->resolved['model'] ?? 'unknown',
        );
        ChatTelemetry::breadcrumb('job.started', ['message_length' => strlen($this->message)]);

        try {
            $agent = resolve(CrmAssistant::class);
            $agent->withConversationId($this->conversationId);
            $agent->continue($this->conversationId, as: $this->user);
            $agent->withMentions($this->mentions);

            $channel = new PrivateChannel("chat.conversation.{$this->conversationId}");

            $response = $agent->stream(
                prompt: $this->message,
                provider: $this->resolved['provider'],
                model: $this->resolved['model'],
            );

            $cancelled = false;
            $cacheKey = "chat:cancel:{$this->conversationId}";

            $response->each(function (StreamEvent $event) use ($channel, $cacheKey, &$cancelled): void {
                if (! $cancelled && Cache::pull($cacheKey) !== null) {
                    $cancelled = true;

                    return;
                }

                if ($cancelled) {
                    return;
                }

                $event->broadcastNow($channel);
            });

            if ($cancelled) {
                $creditService->refundReservation($this->team);
                ChatTelemetry::breadcrumb('stream.cancelled', []);

                return;
            }

            $response->then(function (StreamedAgentResponse $streamedResponse) use ($creditService): void {
                ChatTelemetry::breadcrumb('stream.completed', [
                    'input_tokens' => $streamedResponse->usage->promptTokens,
                    'output_tokens' => $streamedResponse->usage->completionTokens,
                ]);

                broadcast(new ConversationResolved(
                    userId: (string) $this->user->getKey(),
                    conversationId: $streamedResponse->conversationId,
                ));

                $creditService->settleReservation(
                    team: $this->team,
                    user: $this->user,
                    type: AiCreditType::Chat,
                    model: $streamedResponse->meta->model ?? 'unknown',
                    inputTokens: $streamedResponse->usage->promptTokens,
                    outputTokens: $streamedResponse->usage->completionTokens,
                    toolCallsCount: $streamedResponse->toolCalls->count(),
                    conversationId: $streamedResponse->conversationId,
                    idempotencyKey: $streamedResponse->invocationId,
                );

                $this->persistMentions();
                $this->broadcastFollowUps($streamedResponse);
            });
        } finally {
            $this->releaseAuth();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $alreadySettled = AiCreditTransaction::query()
            ->where('conversation_id', $this->conversationId)
            ->where('user_id', $this->user->getKey())
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if (! $alreadySettled) {
            resolve(CreditService::class)->refundReservation($this->team);
        }

        ChatTelemetry::breadcrumb('job.failed', [
            'exception' => $exception?->getMessage(),
            'class' => $exception instanceof Throwable ? $exception::class : null,
        ]);

        broadcast(new ChatStreamFailed(
            conversationId: $this->conversationId,
            message: 'The assistant encountered an error. Please try again.',
        ));
    }

    private function persistMentions(): void
    {
        if ($this->mentions === []) {
            return;
        }

        $userMessageId = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->where('role', 'user')
            ->latest('created_at')
            ->value('id');

        if ($userMessageId === null) {
            return;
        }

        $rows = array_map(static fn (array $m): array => [
            'id' => (string) Str::ulid(),
            'message_id' => $userMessageId,
            'type' => $m['type'],
            'record_id' => $m['id'],
            'label' => $m['label'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->mentions);

        DB::table('agent_conversation_message_mentions')->insert($rows);
    }

    private function broadcastFollowUps(StreamedAgentResponse $streamedResponse): void
    {
        $conversationId = $streamedResponse->conversationId;
        if ($conversationId === null) {
            return;
        }

        $toolCalls = $streamedResponse->toolResults
            ->map(static fn (ToolResult $toolResult): array => [
                'name' => $toolResult->name,
                'result' => $toolResult->result,
            ])
            ->all();

        $chips = resolve(FollowUpService::class)->suggest($toolCalls);

        if ($chips === []) {
            return;
        }

        broadcast(new FollowUpsSuggested(
            conversationId: $conversationId,
            chips: $chips,
        ));
    }

    private function bindAuth(): void
    {
        Auth::guard('web')->setUser($this->user);
    }

    private function releaseAuth(): void
    {
        Auth::guard('web')->forgetUser();
    }
}
