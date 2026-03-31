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

abstract class BaseWriteDeleteTool implements Tool
{
    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** @return class-string */
    abstract protected function actionClass(): string;

    abstract protected function entityLabel(): string;

    abstract protected function entityType(): string;

    abstract public function description(): string;

    protected function nameAttribute(): string
    {
        return 'name';
    }

    public function schema(JsonSchema $schema): array
    {
        $label = strtolower($this->entityLabel());

        return [
            'id' => $schema->string()->description("The {$label} ID to delete.")->required(),
        ];
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

        if ($user->cannot('delete', $model)) {
            return (string) json_encode(['error' => "You do not have permission to delete this {$this->entityLabel()}."]);
        }

        $conversationId = $request['_conversation_id'] ?? 'unknown';
        $entityName = $model->{$this->nameAttribute()};

        $service = app(PendingActionService::class);
        $pending = $service->createProposal(
            user: $user,
            conversationId: $conversationId,
            actionClass: $this->actionClass(),
            operation: PendingActionOperation::Delete,
            entityType: $this->entityType(),
            actionData: [
                '_record_id' => $model->getKey(),
                '_model_class' => $model::class,
            ],
            displayData: [
                'title' => "Delete {$this->entityLabel()}",
                'summary' => "Delete {$this->entityLabel()} \"{$entityName}\"",
                'fields' => [
                    ['label' => 'Name', 'value' => $entityName],
                    ['label' => 'ID', 'value' => $model->getKey()],
                ],
            ],
        );

        return (string) json_encode([
            'type' => 'pending_action',
            'pending_action_id' => $pending->id,
            'action' => class_basename($this->actionClass()),
            'entity_type' => $this->entityType(),
            'operation' => 'delete',
            'data' => ['id' => $model->getKey(), 'name' => $entityName],
            'display' => $pending->display_data,
        ], JSON_PRETTY_PRINT);
    }
}
