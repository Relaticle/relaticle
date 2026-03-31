<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\CreationSource;
use App\Enums\PendingActionOperation;
use App\Enums\PendingActionStatus;
use App\Models\PendingAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final readonly class PendingActionService
{
    /**
     * @param  array<string, mixed>  $actionData
     * @param  array<string, mixed>  $displayData
     */
    public function createProposal(
        User $user,
        string $conversationId,
        string $actionClass,
        PendingActionOperation $operation,
        string $entityType,
        array $actionData,
        array $displayData,
        ?string $messageId = null,
    ): PendingAction {
        $expiryMinutes = (int) config('ai.pending_action_expiry_minutes', 15);

        return PendingAction::query()->create([
            'team_id' => $user->currentTeam->getKey(),
            'user_id' => $user->getKey(),
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'action_class' => $actionClass,
            'operation' => $operation,
            'entity_type' => $entityType,
            'action_data' => $actionData,
            'display_data' => $displayData,
            'status' => PendingActionStatus::Pending,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    public function approve(PendingAction $pendingAction, User $user): PendingAction
    {
        $this->validateResolvable($pendingAction);

        $result = $this->executeAction($pendingAction, $user);

        $resultData = $result instanceof Model
            ? ['id' => $result->getKey(), 'type' => $result->getMorphClass()]
            : ['success' => true];

        $pendingAction->update([
            'status' => PendingActionStatus::Approved,
            'resolved_at' => now(),
            'result_data' => $resultData,
        ]);

        return $pendingAction->refresh();
    }

    public function reject(PendingAction $pendingAction, User $user): PendingAction
    {
        $this->validateResolvable($pendingAction);

        $pendingAction->update([
            'status' => PendingActionStatus::Rejected,
            'resolved_at' => now(),
        ]);

        return $pendingAction->refresh();
    }

    public function expireStale(): int
    {
        return PendingAction::query()
            ->expired()
            ->update([
                'status' => PendingActionStatus::Expired,
                'resolved_at' => now(),
            ]);
    }

    private function validateResolvable(PendingAction $pendingAction): void
    {
        if (! $pendingAction->isPending()) {
            throw new RuntimeException('This action has already been resolved');
        }

        if ($pendingAction->isExpired()) {
            throw new RuntimeException('This action has expired');
        }
    }

    private function executeAction(PendingAction $pendingAction, User $user): mixed
    {
        $actionClass = $pendingAction->action_class;
        $action = app()->make($actionClass);
        $data = $pendingAction->action_data;

        return match ($pendingAction->operation) {
            PendingActionOperation::Create => $action->execute($user, $data, CreationSource::CHAT),
            PendingActionOperation::Update => $this->executeUpdate($action, $user, $pendingAction),
            PendingActionOperation::Delete => $this->executeDelete($action, $user, $pendingAction),
        };
    }

    private function executeUpdate(object $action, User $user, PendingAction $pendingAction): mixed
    {
        $data = $pendingAction->action_data;
        $modelId = $data['_record_id'] ?? null;
        $modelClass = $data['_model_class'] ?? null;

        unset($data['_record_id'], $data['_model_class']);

        /** @var class-string<Model> $modelClass */
        $model = $modelClass::query()->findOrFail($modelId);

        if (! method_exists($action, 'execute')) {
            throw new RuntimeException("Action class {$pendingAction->action_class} does not have an execute method");
        }

        return $action->execute($user, $model, $data);
    }

    private function executeDelete(object $action, User $user, PendingAction $pendingAction): mixed
    {
        $data = $pendingAction->action_data;
        $modelId = $data['_record_id'] ?? null;
        $modelClass = $data['_model_class'] ?? null;

        /** @var class-string<Model> $modelClass */
        $model = $modelClass::query()->findOrFail($modelId);

        if (! method_exists($action, 'execute')) {
            throw new RuntimeException("Action class {$pendingAction->action_class} does not have an execute method");
        }

        $action->execute($user, $model);

        return null;
    }
}
