<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Models\PendingAction;
use App\Models\User;
use App\Services\AI\PendingActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PendingActionController
{
    public function __construct(
        private readonly PendingActionService $service,
    ) {}

    public function approve(Request $request, PendingAction $pendingAction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($pendingAction->team_id !== $user->currentTeam->getKey()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($pendingAction->user_id !== $user->getKey()) {
            return response()->json(['error' => 'You can only approve your own actions'], 403);
        }

        try {
            $result = $this->service->approve($pendingAction, $user);

            return response()->json([
                'status' => 'approved',
                'result_data' => $result->result_data,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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
            return response()->json(['error' => 'You can only reject your own actions'], 403);
        }

        try {
            $this->service->reject($pendingAction, $user);

            return response()->json(['status' => 'rejected']);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
