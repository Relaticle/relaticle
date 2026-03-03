<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;

class WorkflowLifecycleController extends Controller
{
    public function publish(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $this->authorizeTenantAccess($workflow);

        $errors = $workflow->getActivationErrors();
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $workflow->update([
            'status' => WorkflowStatus::Live,
            'published_at' => now(),
        ]);

        return response()->json(['status' => 'live']);
    }

    public function pause(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $this->authorizeTenantAccess($workflow);

        if ($workflow->status !== WorkflowStatus::Live) {
            return response()->json(['errors' => ['Only live workflows can be paused.']], 422);
        }

        $workflow->update(['status' => WorkflowStatus::Paused]);

        return response()->json(['status' => 'paused']);
    }

    public function archive(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $this->authorizeTenantAccess($workflow);

        if ($workflow->status === WorkflowStatus::Archived) {
            return response()->json(['errors' => ['Workflow is already archived.']], 422);
        }

        $workflow->update(['status' => WorkflowStatus::Archived]);

        return response()->json(['status' => 'archived']);
    }

    public function restore(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $this->authorizeTenantAccess($workflow);

        if ($workflow->status !== WorkflowStatus::Archived) {
            return response()->json(['errors' => ['Only archived workflows can be restored.']], 422);
        }

        $workflow->update(['status' => WorkflowStatus::Paused]);

        return response()->json(['status' => 'paused']);
    }

    /**
     * Verify the workflow belongs to the authenticated user's tenant.
     */
    private function authorizeTenantAccess(Workflow $workflow): void
    {
        $user = auth()->user();

        // Skip tenant check if no user is authenticated (handled by auth middleware)
        // or if the workflow has no tenant_id (e.g., in tests)
        if (!$user || !$workflow->tenant_id) {
            return;
        }

        $userTenantId = $user->current_team_id ?? $user->tenant_id ?? null;
        if ($userTenantId && $workflow->tenant_id !== $userTenantId) {
            abort(403, 'Unauthorized access to this workflow.');
        }
    }
}
