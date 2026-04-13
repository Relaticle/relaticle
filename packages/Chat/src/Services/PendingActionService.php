<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Enums\PendingActionOperation;
use Relaticle\Chat\Enums\PendingActionStatus;
use Relaticle\Chat\Models\PendingAction;
use RuntimeException;

final readonly class PendingActionService
{
    /** @var list<class-string<Model>> */
    private const array ALLOWED_MODEL_CLASSES = [
        Company::class,
        People::class,
        Opportunity::class,
        Task::class,
        Note::class,
    ];

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
        $expiryMinutes = (int) config('chat.pending_action_expiry_minutes', 15);

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
        return DB::transaction(function () use ($pendingAction, $user): PendingAction {
            /** @var PendingAction $pendingAction */
            $pendingAction = PendingAction::query()
                ->lockForUpdate()
                ->findOrFail($pendingAction->getKey());

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
        });
    }

    public function reject(PendingAction $pendingAction): PendingAction
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
        throw_unless($pendingAction->isPending(), RuntimeException::class, 'This action has already been resolved');

        throw_if($pendingAction->isExpired(), RuntimeException::class, 'This action has expired');
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
        $modelClass = $this->resolveModelClass($data);

        unset($data['_record_id'], $data['_model_class']);

        $model = $this->resolveModel($modelClass, $pendingAction);

        if (! method_exists($action, 'execute')) {
            throw new RuntimeException("Action class {$pendingAction->action_class} does not have an execute method");
        }

        return $action->execute($user, $model, $data);
    }

    private function executeDelete(object $action, User $user, PendingAction $pendingAction): mixed
    {
        $data = $pendingAction->action_data;
        $modelClass = $this->resolveModelClass($data);

        $model = $this->resolveModel($modelClass, $pendingAction);

        if (! method_exists($action, 'execute')) {
            throw new RuntimeException("Action class {$pendingAction->action_class} does not have an execute method");
        }

        $action->execute($user, $model);

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return class-string<Model>
     */
    private function resolveModelClass(array $data): string
    {
        $modelClass = $data['_model_class'] ?? null;

        throw_if(! is_string($modelClass) || ! in_array($modelClass, self::ALLOWED_MODEL_CLASSES, true), RuntimeException::class, "Invalid model class: {$modelClass}");

        return $modelClass;
    }

    private function resolveModel(string $modelClass, PendingAction $pendingAction): Model
    {
        $recordId = $pendingAction->action_data['_record_id'] ?? null;

        throw_if(! is_string($recordId) && ! is_int($recordId), RuntimeException::class, 'Missing or invalid _record_id in action data');

        return $modelClass::query()
            ->where('team_id', $pendingAction->team_id)
            ->findOrFail($recordId);
    }
}
