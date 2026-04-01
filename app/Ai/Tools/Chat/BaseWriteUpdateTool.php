<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat;

use App\Enums\PendingActionOperation;
use App\Models\User;
use App\Services\AI\PendingActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

abstract class BaseWriteUpdateTool implements Tool
{
    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** @return class-string */
    abstract protected function actionClass(): string;

    abstract protected function entityType(): string;

    abstract protected function entityLabel(): string;

    abstract public function description(): string;

    /** @return array<string, mixed> */
    abstract protected function entitySchema(JsonSchema $schema): array;

    /** @return array<string, mixed> */
    abstract protected function buildDisplayData(Request $request, Model $model): array;

    /** @return array<string, mixed> */
    abstract protected function extractActionData(Request $request): array;

    public function schema(JsonSchema $schema): array
    {
        $label = strtolower($this->entityLabel());

        return array_merge(
            ['id' => $schema->string()->description("The {$label} ID to update.")->required()],
            $this->entitySchema($schema),
        );
    }

    public function handle(Request $request): string
    {
        /** @var User $user */
        $user = auth()->user();

        $id = $request->string('id');
        $modelClass = $this->modelClass();
        $model = $modelClass::query()->find($id);

        if (! $model instanceof Model) {
            return (string) json_encode(['error' => "{$this->entityLabel()} with ID [{$id}] not found."]);
        }

        if ($user->cannot('update', $model)) {
            return (string) json_encode(['error' => "You do not have permission to update this {$this->entityLabel()}."]);
        }

        $conversationId = $request['_conversation_id'] ?? 'unknown';
        $actionData = $this->extractActionData($request);
        $actionData['_record_id'] = $model->getKey();
        $actionData['_model_class'] = $model::class;

        $service = resolve(PendingActionService::class);
        $pending = $service->createProposal(
            user: $user,
            conversationId: $conversationId,
            actionClass: $this->actionClass(),
            operation: PendingActionOperation::Update,
            entityType: $this->entityType(),
            actionData: $actionData,
            displayData: $this->buildDisplayData($request, $model),
        );

        return (string) json_encode([
            'type' => 'pending_action',
            'pending_action_id' => $pending->id,
            'action' => class_basename($this->actionClass()),
            'entity_type' => $this->entityType(),
            'operation' => 'update',
            'data' => array_diff_key($pending->action_data, array_flip(['_record_id', '_model_class'])),
            'display' => $pending->display_data,
        ], JSON_PRETTY_PRINT);
    }
}
