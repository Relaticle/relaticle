<?php

declare(strict_types=1);

namespace Relaticle\Chat\Jobs;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Scopes\TeamScope;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Relaticle\Chat\Agents\CrmAssistant;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Events\ChatStreamFailed;
use Relaticle\Chat\Events\ConversationResolved;
use Relaticle\Chat\Services\CreditService;
use Relaticle\Chat\Support\ChatTelemetry;
use Throwable;

final class ProcessChatMessage implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    /**
     * @param  array{provider: string|null, model: string|null}  $resolved
     */
    public function __construct(
        private readonly User $user,
        private readonly Team $team,
        private readonly string $message,
        private readonly string $conversationId,
        private readonly array $resolved,
    ) {
        $this->onQueue('chat');
    }

    public function handle(CreditService $creditService): void
    {
        $this->bindAuthAndScopes();

        ChatTelemetry::tagCurrentScope(
            $this->conversationId,
            (string) $this->team->getKey(),
            $this->resolved['model'] ?? 'unknown',
        );
        ChatTelemetry::breadcrumb('job.started', ['message_length' => strlen($this->message)]);

        try {
            $agent = resolve(CrmAssistant::class);
            $agent->continue($this->conversationId, as: $this->user);

            $channel = new PrivateChannel("chat.conversation.{$this->conversationId}");

            $response = $agent->stream(
                prompt: $this->message,
                provider: $this->resolved['provider'],
                model: $this->resolved['model'],
            );

            $response->each(function (StreamEvent $event) use ($channel): void {
                $event->broadcastNow($channel);
            });

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
                );
            });
        } finally {
            $this->releaseScopes();
        }
    }

    public function failed(?Throwable $exception): void
    {
        resolve(CreditService::class)->refundReservation($this->team);

        ChatTelemetry::breadcrumb('job.failed', [
            'exception' => $exception?->getMessage(),
            'class' => $exception instanceof Throwable ? $exception::class : null,
        ]);

        broadcast(new ChatStreamFailed(
            conversationId: $this->conversationId,
            message: 'The assistant encountered an error. Please try again.',
        ));
    }

    private function bindAuthAndScopes(): void
    {
        Auth::guard('web')->setUser($this->user);

        Company::addGlobalScope(new TeamScope);
        People::addGlobalScope(new TeamScope);
        Opportunity::addGlobalScope(new TeamScope);
        Task::addGlobalScope(new TeamScope);
        Note::addGlobalScope(new TeamScope);
    }

    private function releaseScopes(): void
    {
        Auth::guard('web')->forgetUser();
    }
}
