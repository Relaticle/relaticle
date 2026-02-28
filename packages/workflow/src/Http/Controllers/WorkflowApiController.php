<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
}
