<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowRun;

class RunController extends Controller
{
    public function index(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $runs = $workflow->runs()
            ->orderByDesc('started_at')
            ->limit(50)
            ->get(['id', 'status', 'started_at', 'completed_at', 'error_message']);

        return response()->json(['runs' => $runs]);
    }

    public function show(string $runId): JsonResponse
    {
        $run = WorkflowRun::with(['steps.node'])->findOrFail($runId);

        return response()->json([
            'run' => [
                'id' => $run->id,
                'status' => $run->status,
                'started_at' => $run->started_at,
                'completed_at' => $run->completed_at,
                'error_message' => $run->error_message,
                'context_data' => $run->context_data,
                'steps' => $run->steps->map(fn ($step) => [
                    'id' => $step->id,
                    'node_id' => $step->node?->node_id,
                    'status' => $step->status,
                    'input_data' => $step->input_data,
                    'output_data' => $step->output_data,
                    'error_message' => $step->error_message,
                    'started_at' => $step->started_at,
                    'completed_at' => $step->completed_at,
                ]),
            ],
        ]);
    }
}
