<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Relaticle\Chat\Enums\PendingActionOperation;
use Relaticle\Chat\Services\PendingActionService;
use Relaticle\Chat\Tools\Concerns\WithConversationContext;

abstract class BaseWriteCreateTool implements Tool
{
    use WithConversationContext;

    /** @return class-string */
    abstract protected function actionClass(): string;

    abstract protected function entityType(): string;

    abstract public function description(): string;

    /** @return array<string, mixed> */
    abstract protected function entitySchema(JsonSchema $schema): array;

    /** @return array<string, mixed> */
    abstract protected function buildDisplayData(Request $request): array;

    /** @return array<string, mixed> */
    abstract protected function extractActionData(Request $request): array;

    public function schema(JsonSchema $schema): array
    {
        return $this->entitySchema($schema);
    }

    public function handle(Request $request): string
    {
        /** @var User $user */
        $user = auth()->user();

        $conversationId = $this->resolveConversationId();

        $service = resolve(PendingActionService::class);
        $pending = $service->createProposal(
            user: $user,
            conversationId: $conversationId,
            actionClass: $this->actionClass(),
            operation: PendingActionOperation::Create,
            entityType: $this->entityType(),
            actionData: $this->extractActionData($request),
            displayData: $this->buildDisplayData($request),
        );

        return (string) json_encode([
            'type' => 'pending_action',
            'pending_action_id' => $pending->id,
            'action' => class_basename($this->actionClass()),
            'entity_type' => $this->entityType(),
            'operation' => 'create',
            'data' => $pending->action_data,
            'display' => $pending->display_data,
        ], JSON_PRETTY_PRINT);
    }
}
