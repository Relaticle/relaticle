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

        if ($workflow->status !== WorkflowStatus::Live) {
            return response()->json(['errors' => ['Only live workflows can be paused.']], 422);
        }

        $workflow->update(['status' => WorkflowStatus::Paused]);

        return response()->json(['status' => 'paused']);
    }

    public function archive(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);

        if ($workflow->status === WorkflowStatus::Archived) {
            return response()->json(['errors' => ['Workflow is already archived.']], 422);
        }

        $workflow->update(['status' => WorkflowStatus::Archived]);

        return response()->json(['status' => 'archived']);
    }

    public function restore(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);

        if ($workflow->status !== WorkflowStatus::Archived) {
            return response()->json(['errors' => ['Only archived workflows can be restored.']], 422);
        }

        $workflow->update(['status' => WorkflowStatus::Paused]);

        return response()->json(['status' => 'paused']);
    }
}
