<?php

declare(strict_types=1);

namespace Relaticle\Chat\Http\Controllers;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\NoteResource;
use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\PeopleResource;
use App\Filament\Resources\TaskResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Relaticle\Chat\Models\PendingAction;
use Relaticle\Chat\Services\PendingActionService;
use RuntimeException;
use Throwable;

final readonly class PendingActionController
{
    public function __construct(
        private PendingActionService $service,
    ) {}

    public function approve(Request $request, PendingAction $pendingAction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($pendingAction->team_id !== $user->currentTeam->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($pendingAction->user_id !== $user->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        try {
            $result = $this->service->approve($pendingAction, $user);

            return response()->json([
                'status' => 'approved',
                'result_data' => $result->result_data,
                'record' => $this->resolveRecordReference($result),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * @return array{id: string, type: string, url: string}|null
     */
    private function resolveRecordReference(PendingAction $pendingAction): ?array
    {
        $resultData = $pendingAction->result_data;
        $recordId = is_array($resultData) ? ($resultData['id'] ?? null) : null;

        if (! is_string($recordId) && ! is_int($recordId)) {
            return null;
        }

        $entityType = $pendingAction->entity_type;
        $url = $this->resolveResourceUrl($entityType, (string) $recordId);

        if ($url === null) {
            return null;
        }

        return [
            'id' => (string) $recordId,
            'type' => $entityType,
            'url' => $url,
        ];
    }

    private function resolveResourceUrl(string $entityType, string $recordId): ?string
    {
        try {
            return match ($entityType) {
                'company' => CompanyResource::getUrl('view', ['record' => $recordId]),
                'people' => PeopleResource::getUrl('view', ['record' => $recordId]),
                'opportunity' => OpportunityResource::getUrl('view', ['record' => $recordId]),
                'task' => TaskResource::getUrl('index'),
                'note' => NoteResource::getUrl('index'),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    public function reject(Request $request, PendingAction $pendingAction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($pendingAction->team_id !== $user->currentTeam->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($pendingAction->user_id !== $user->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        try {
            $this->service->reject($pendingAction);

            return response()->json(['status' => 'rejected']);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function restore(Request $request, PendingAction $pendingAction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($pendingAction->team_id !== $user->currentTeam->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($pendingAction->user_id !== $user->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        try {
            $result = $this->service->restore($pendingAction, $user);

            return response()->json([
                'status' => 'restored',
                'record' => $this->resolveDeletedRecordReference($result),
            ]);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'undo_window_expired') {
                return response()->json(['error' => 'undo_window_expired'], 410);
            }

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * @return array{id: string, type: string, url: string}|null
     */
    private function resolveDeletedRecordReference(PendingAction $pendingAction): ?array
    {
        $recordId = $pendingAction->action_data['_record_id'] ?? null;

        if (! is_string($recordId) && ! is_int($recordId)) {
            return null;
        }

        $entityType = $pendingAction->entity_type;
        $url = $this->resolveResourceUrl($entityType, (string) $recordId);

        if ($url === null) {
            return null;
        }

        return [
            'id' => (string) $recordId,
            'type' => $entityType,
            'url' => $url,
        ];
    }
}
