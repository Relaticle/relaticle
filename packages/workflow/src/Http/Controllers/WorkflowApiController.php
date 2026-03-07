<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Engine\WorkflowExecutor;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\ManualTrigger;

class WorkflowApiController extends Controller
{
    /**
     * Trigger a manual workflow execution.
     *
     * Accepts an optional JSON body with a "context" key containing
     * arbitrary data to pass to the workflow (e.g. record information).
     */
    public function trigger(Request $request, string $workflowId, ManualTrigger $manualTrigger): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);

        try {
            $manualTrigger->trigger($workflow, $request->input('context', []));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Workflow triggered successfully.']);
    }

    /**
     * Execute a workflow in test mode (real execution).
     *
     * Returns a step-by-step execution trace showing what happened.
     */
    public function testRun(Request $request, string $workflowId, WorkflowExecutor $executor): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $context = $request->input('context', []);

        try {
            $run = $executor->execute($workflow, $context);

            $steps = $run->steps->map(fn ($step) => [
                'node_id' => $step->node->node_id ?? null,
                'node_type' => $step->node->type?->value ?? null,
                'action_type' => $step->node->action_type ?? null,
                'action_label' => $step->node->action_type
                    ? ($this->getActionLabel($step->node->action_type) ?? $step->node->action_type)
                    : ($step->node->type?->value ?? 'unknown'),
                'status' => $step->status->value,
                'input' => $step->input_data,
                'output' => $step->output_data,
                'error' => $step->error_message,
            ]);

            return response()->json([
                'run_id' => $run->id,
                'status' => $run->status->value,
                'steps' => $steps,
                'error' => $run->error_message,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getActionLabel(string $actionType): ?string
    {
        $actions = app(\Relaticle\Workflow\WorkflowManager::class)->getRegisteredActions();
        $actionClass = $actions[$actionType] ?? null;

        return $actionClass ? $actionClass::label() : null;
    }
}
