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
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Relaticle\Chat\Agents\CrmAssistant;
use Relaticle\Chat\Enums\AiCreditType;
use Relaticle\Chat\Events\ConversationResolved;
use Relaticle\Chat\Services\CreditService;

final class ProcessChatMessage implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    /**
     * @param  array{provider: string, model: string}  $resolved
     */
    public function __construct(
        private readonly User $user,
        private readonly Team $team,
        private readonly string $message,
        private readonly ?string $conversationId,
        private readonly array $resolved,
    ) {}

    public function handle(CreditService $creditService): void
    {
        $this->applyTenantScopes();

        $agent = resolve(CrmAssistant::class);

        if ($this->conversationId !== null) {
            $agent->continue($this->conversationId, as: $this->user);
        } else {
            $agent->forUser($this->user);
        }

        $channel = new PrivateChannel("chat.{$this->user->getKey()}");

        $response = $agent->stream(
            prompt: $this->message,
            provider: $this->resolved['provider'],
            model: $this->resolved['model'],
        );

        $response->each(function (StreamEvent $event) use ($channel): void {
            $event->broadcastNow($channel);
        });

        $response->then(function (StreamedAgentResponse $streamedResponse) use ($creditService): void {
            broadcast(new ConversationResolved(
                userId: (string) $this->user->getKey(),
                conversationId: $streamedResponse->conversationId,
            ));

            $creditService->deduct(
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
