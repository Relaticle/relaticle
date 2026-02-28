<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Engine;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Events\WorkflowRunCompleted;
use Relaticle\Workflow\Events\WorkflowRunFailed;
use Relaticle\Workflow\Events\WorkflowTriggered;
use Relaticle\Workflow\Jobs\ExecuteStepJob;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowNode;
use Relaticle\Workflow\Models\WorkflowRun;
use Relaticle\Workflow\Models\WorkflowRunStep;
use Relaticle\Workflow\WorkflowManager;

class WorkflowExecutor
{
    public function __construct(
        private readonly WorkflowManager $manager,
        private readonly ConditionEvaluator $conditionEvaluator,
        private readonly VariableResolver $variableResolver,
    ) {}

    /**
     * Execute a workflow with the given context.
     *
     * Creates a WorkflowRun record, walks the workflow graph using BFS,
     * and executes each node according to its type. Returns the completed
     * (or failed) WorkflowRun with its steps loaded.
     *
     * @param  array<string, mixed>  $context
     */
    public function execute(Workflow $workflow, array $context): WorkflowRun
    {
        WorkflowTriggered::dispatch($workflow, $context);

        $run = $this->createRun($workflow, $context);

        try {
            DB::transaction(function () use ($workflow, $run, $context): void {
                $workflow->load(['nodes', 'edges']);

                $walker = new GraphWalker($workflow->nodes, $workflow->edges);
                $triggerNode = $walker->findTriggerNode();

                if ($triggerNode === null) {
                    throw new \RuntimeException('No trigger node found in workflow.');
                }

                $this->walkGraph($walker, $triggerNode, $run, $context);
            });

            // Only complete if not paused by a delay
            if ($run->fresh()->status !== WorkflowRunStatus::Paused) {
                $this->completeRun($run);
            }
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
        }

        $run->load('steps');

        return $run;
    }

    /**
     * Resume a paused workflow run from the given node ID.
     *
     * This is called by ExecuteStepJob after a delay period has elapsed.
     * It sets the run back to Running, finds the delay node, and continues
     * executing from the nodes after that delay node.
     *
     * @param  array<string, mixed>  $context
     */
    public function resume(WorkflowRun $run, string $resumeFromNodeId, array $context): WorkflowRun
    {
        $workflow = $run->workflow;
        $run->update(['status' => WorkflowRunStatus::Running]);

        try {
            DB::transaction(function () use ($workflow, $run, $resumeFromNodeId, $context): void {
                $workflow->load(['nodes', 'edges']);

                $walker = new GraphWalker($workflow->nodes, $workflow->edges);

                // Find the delay node we're resuming from (by node_id, not database id)
                $delayNode = $workflow->nodes->firstWhere('node_id', $resumeFromNodeId);
                if ($delayNode === null) {
                    throw new \RuntimeException("Resume node {$resumeFromNodeId} not found.");
                }

                // Continue from nodes after the delay
                $nextNodes = $walker->getNextNodes($delayNode);

                /** @var \SplQueue<WorkflowNode> $queue */
                $queue = new \SplQueue();
                foreach ($nextNodes as $next) {
                    $queue->enqueue($next);
                }

                $processedNodeIds = [];
                $stepCount = 0;
                $maxSteps = (int) config('workflow.max_steps_per_run', 100);

                while (! $queue->isEmpty()) {
                    /** @var WorkflowNode $node */
                    $node = $queue->dequeue();

                    if (in_array($node->id, $processedNodeIds, true)) {
                        continue;
                    }

                    $processedNodeIds[] = $node->id;

                    $stepCount++;
                    if ($stepCount > $maxSteps) {
                        throw new \RuntimeException('Maximum step limit exceeded.');
                    }

                    match ($node->type) {
                        NodeType::Action => $this->executeActionNode($node, $walker, $run, $context, $queue),
                        NodeType::Condition => $this->executeConditionNode($node, $walker, $run, $context, $queue),
                        NodeType::Delay => $this->handleDelayPause($run, $node, $context),
                        NodeType::Loop => $this->executeLoopNode($node, $walker, $run, $context, $processedNodeIds),
                        NodeType::Stop => null,
                        default => $this->enqueueNextNodes($walker, $node, $queue),
                    };

                    // If the run was paused (by another delay node), stop walking
                    if ($run->fresh()->status === WorkflowRunStatus::Paused) {
                        return;
                    }
                }
            });

            // Only complete if not paused by another delay
            if ($run->fresh()->status !== WorkflowRunStatus::Paused) {
                $this->completeRun($run);
            }
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
        }

        $run->load('steps');

        return $run;
    }

    /**
     * Create an initial WorkflowRun record in Running state.
     *
     * @param  array<string, mixed>  $context
     */
    private function createRun(Workflow $workflow, array $context): WorkflowRun
    {
        /** @var WorkflowRun $run */
        $run = $workflow->runs()->create([
            'status' => WorkflowRunStatus::Running,
            'context_data' => $context,
            'started_at' => Carbon::now(),
        ]);

        return $run;
    }

    /**
     * Walk the workflow graph using BFS starting from the trigger node.
     *
     * @param  array<string, mixed>  $context
     */
    private function walkGraph(
        GraphWalker $walker,
        WorkflowNode $triggerNode,
        WorkflowRun $run,
        array $context,
    ): void {
        /** @var \SplQueue<WorkflowNode> $queue */
        $queue = new \SplQueue();

        // Start by queueing the next nodes after the trigger
        foreach ($walker->getNextNodes($triggerNode) as $node) {
            $queue->enqueue($node);
        }

        $processedNodeIds = [];
        $stepCount = 0;
        $maxSteps = (int) config('workflow.max_steps_per_run', 100);

        while (! $queue->isEmpty()) {
            /** @var WorkflowNode $currentNode */
            $currentNode = $queue->dequeue();

            // Skip already-processed nodes to avoid cycles
            if (in_array($currentNode->id, $processedNodeIds, true)) {
                continue;
            }

            $stepCount++;
            if ($stepCount > $maxSteps) {
                throw new \RuntimeException("Workflow exceeded maximum of {$maxSteps} steps per run.");
            }

            $processedNodeIds[] = $currentNode->id;

            match ($currentNode->type) {
                NodeType::Action => $this->executeActionNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Condition => $this->executeConditionNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Delay => $this->handleDelayPause($run, $currentNode, $context),
                NodeType::Loop => $this->executeLoopNode($currentNode, $walker, $run, $context, $processedNodeIds),
                NodeType::Stop => null, // Stop node: do nothing, just don't enqueue further nodes
                default => $this->enqueueNextNodes($walker, $currentNode, $queue),
            };

            // If the run was paused (by a delay node), stop walking
            if ($run->fresh()->status === WorkflowRunStatus::Paused) {
                return;
            }
        }
    }

    /**
     * Execute an action node: resolve config, run the action, record the step.
     *
     * @param  \SplQueue<WorkflowNode>  $queue
     * @param  array<string, mixed>  $context
     */
    private function executeActionNode(
        WorkflowNode $node,
        GraphWalker $walker,
        WorkflowRun $run,
        array $context,
        \SplQueue $queue,
    ): void {
        $step = $this->createStep($run, $node, StepStatus::Running);

        $actionKey = $node->action_type;
        $registeredActions = $this->manager->getRegisteredActions();

        if (! isset($registeredActions[$actionKey])) {
            throw new \RuntimeException("Action [{$actionKey}] is not registered.");
        }

        /** @var class-string<WorkflowAction> $actionClass */
        $actionClass = $registeredActions[$actionKey];

        $resolvedConfig = $this->variableResolver->resolveArray($node->config ?? [], $context);

        $this->validateActionConfig($actionKey, $resolvedConfig);

        $step->update([
            'input_data' => $resolvedConfig,
            'started_at' => Carbon::now(),
        ]);

        /** @var WorkflowAction $action */
        $action = new $actionClass();
        $output = $action->execute($resolvedConfig, $context);

        $step->update([
            'status' => StepStatus::Completed,
            'output_data' => $output,
            'completed_at' => Carbon::now(),
        ]);

        $this->enqueueNextNodes($walker, $node, $queue);
    }

    /**
     * Handle a delay node by pausing the run and dispatching a delayed resume job.
     *
     * Instead of continuing execution immediately, this method records the delay
     * step, pauses the workflow run, and dispatches an ExecuteStepJob that will
     * resume execution after the configured delay period.
     *
     * @param  array<string, mixed>  $context
     */
    private function handleDelayPause(WorkflowRun $run, WorkflowNode $node, array $context): void
    {
        $resolvedConfig = $this->variableResolver->resolveArray($node->config ?? [], $context);

        $delayAction = new \Relaticle\Workflow\Actions\DelayAction();
        $output = $delayAction->execute($resolvedConfig, $context);

        // Record the delay step as completed
        $step = $this->createStep($run, $node, StepStatus::Completed);
        $step->update([
            'input_data' => $resolvedConfig,
            'output_data' => $output,
            'completed_at' => Carbon::now(),
        ]);

        // Pause the run
        $run->update(['status' => WorkflowRunStatus::Paused]);

        // Dispatch delayed resume job
        $delaySeconds = $output['delay_seconds'] ?? 0;
        ExecuteStepJob::dispatch($run, $node->node_id, $context)
            ->delay(now()->addSeconds($delaySeconds));
    }

    /**
     * Execute a loop node: iterate over a collection and execute downstream nodes for each item.
     *
     * The loop resolves a collection from the context using a dot-notation path,
     * then for each item it enriches the context with `loop.item` and `loop.index`
     * and executes the downstream sub-graph. Loop body nodes are processed inline
     * (not via the main BFS queue) and are marked as processed in the parent walk
     * to prevent re-execution.
     *
     * @param  array<string, mixed>  $context
     * @param  array<int|string>  $processedNodeIds  Passed by reference so loop body nodes are tracked
     */
    private function executeLoopNode(
        WorkflowNode $node,
        GraphWalker $walker,
        WorkflowRun $run,
        array $context,
        array &$processedNodeIds,
    ): void {
        // Record the loop step itself
        $step = $this->createStep($run, $node, StepStatus::Running);

        $resolvedConfig = $this->variableResolver->resolveArray($node->config ?? [], $context);

        $step->update([
            'input_data' => $resolvedConfig,
            'started_at' => Carbon::now(),
        ]);

        $action = new \Relaticle\Workflow\Actions\LoopAction();
        $output = $action->execute($resolvedConfig, $context);

        $step->update([
            'status' => StepStatus::Completed,
            'output_data' => $output,
            'completed_at' => Carbon::now(),
        ]);

        // Resolve the collection from context
        $collectionPath = $resolvedConfig['collection'] ?? '';
        $items = data_get($context, $collectionPath, []);

        if (! is_array($items)) {
            $items = [];
        }

        $maxIterations = (int) config('workflow.max_loop_iterations', 500);
        if (count($items) > $maxIterations) {
            throw new \RuntimeException("Loop exceeds maximum of {$maxIterations} iterations (has " . count($items) . ').');
        }

        // Get the downstream nodes of the loop
        $downstreamNodes = $walker->getNextNodes($node);

        // Mark downstream nodes as processed in the parent walk so they
        // are not re-executed after the loop completes
        $loopBodyNodeIds = $this->collectDescendantNodeIds($walker, $node);
        foreach ($loopBodyNodeIds as $bodyNodeId) {
            $processedNodeIds[] = $bodyNodeId;
        }

        // For each item, execute the downstream sub-graph with enriched context
        foreach (array_values($items) as $index => $item) {
            $iterationContext = array_merge($context, [
                'loop' => [
                    'item' => $item,
                    'index' => $index,
                ],
            ]);

            $this->walkLoopBody($walker, $downstreamNodes, $run, $iterationContext);
        }
    }

    /**
     * Walk the loop body sub-graph using BFS for a single loop iteration.
     *
     * Each iteration gets its own processed-node tracking so that the same
     * node structure can be executed once per iteration without interference.
     *
     * @param  \Illuminate\Support\Collection<int, WorkflowNode>  $startNodes
     * @param  array<string, mixed>  $context
     */
    private function walkLoopBody(
        GraphWalker $walker,
        \Illuminate\Support\Collection $startNodes,
        WorkflowRun $run,
        array $context,
    ): void {
        /** @var \SplQueue<WorkflowNode> $queue */
        $queue = new \SplQueue();

        foreach ($startNodes as $node) {
            $queue->enqueue($node);
        }

        $processedNodeIds = [];

        while (! $queue->isEmpty()) {
            /** @var WorkflowNode $currentNode */
            $currentNode = $queue->dequeue();

            if (in_array($currentNode->id, $processedNodeIds, true)) {
                continue;
            }

            $processedNodeIds[] = $currentNode->id;

            match ($currentNode->type) {
                NodeType::Action => $this->executeActionNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Condition => $this->executeConditionNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Delay => $this->handleDelayPause($run, $currentNode, $context),
                NodeType::Stop => null,
                default => $this->enqueueNextNodes($walker, $currentNode, $queue),
            };

            // If the run was paused (by a delay node), stop walking
            if ($run->fresh()->status === WorkflowRunStatus::Paused) {
                return;
            }
        }
    }

    /**
     * Collect all descendant node IDs reachable from a given node.
     *
     * Uses BFS to traverse the sub-graph and returns all node IDs found.
     * This is used to mark loop body nodes as processed in the parent walk.
     *
     * @return array<int|string>
     */
    private function collectDescendantNodeIds(GraphWalker $walker, WorkflowNode $node): array
    {
        $ids = [];
        $visited = [];

        /** @var \SplQueue<WorkflowNode> $queue */
        $queue = new \SplQueue();

        foreach ($walker->getNextNodes($node) as $nextNode) {
            $queue->enqueue($nextNode);
        }

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();

            if (in_array($current->id, $visited, true)) {
                continue;
            }

            $visited[] = $current->id;
            $ids[] = $current->id;

            foreach ($walker->getNextNodes($current) as $nextNode) {
                $queue->enqueue($nextNode);
            }
        }

        return $ids;
    }

    /**
     * Execute a condition node: evaluate the condition, follow the matching branch,
     * and mark the other branch's immediate target as Skipped.
     *
     * @param  \SplQueue<WorkflowNode>  $queue
     * @param  array<string, mixed>  $context
     */
    private function executeConditionNode(
        WorkflowNode $node,
        GraphWalker $walker,
        WorkflowRun $run,
        array $context,
        \SplQueue $queue,
    ): void {
        $config = $node->config ?? [];
        $result = $this->conditionEvaluator->evaluate($config, $context);

        $takenLabel = $result ? 'yes' : 'no';
        $skippedLabel = $result ? 'no' : 'yes';

        // Follow the taken branch
        $takenEdge = $walker->getEdgeByLabel($node, $takenLabel);
        if ($takenEdge !== null) {
            $takenTarget = $this->findNodeById($walker, $takenEdge->target_node_id);
            if ($takenTarget !== null) {
                $queue->enqueue($takenTarget);
            }
        }

        // Mark the skipped branch's immediate target node as Skipped
        $skippedEdge = $walker->getEdgeByLabel($node, $skippedLabel);
        if ($skippedEdge !== null) {
            $skippedTarget = $this->findNodeById($walker, $skippedEdge->target_node_id);
            if ($skippedTarget !== null) {
                $this->createStep($run, $skippedTarget, StepStatus::Skipped);
            }
        }
    }

    /**
     * Find a WorkflowNode by its database ID within the graph walker's node set.
     */
    private function findNodeById(GraphWalker $walker, string $nodeId): ?WorkflowNode
    {
        return $walker->findNodeById($nodeId);
    }

    /**
     * Enqueue all next nodes from the given node into the BFS queue.
     *
     * @param  \SplQueue<WorkflowNode>  $queue
     */
    private function enqueueNextNodes(GraphWalker $walker, WorkflowNode $node, \SplQueue $queue): void
    {
        foreach ($walker->getNextNodes($node) as $nextNode) {
            $queue->enqueue($nextNode);
        }
    }

    /**
     * Create a WorkflowRunStep record.
     */
    private function createStep(WorkflowRun $run, WorkflowNode $node, StepStatus $status): WorkflowRunStep
    {
        /** @var WorkflowRunStep $step */
        $step = $run->steps()->create([
            'workflow_node_id' => $node->id,
            'status' => $status,
            'started_at' => $status === StepStatus::Running ? Carbon::now() : null,
        ]);

        return $step;
    }

    /**
     * Validate action config against the action's configSchema() using Laravel Validator.
     *
     * @param  array<string, mixed>  $config
     */
    private function validateActionConfig(string $actionType, array $config): void
    {
        $actions = $this->manager->getRegisteredActions();
        $actionClass = $actions[$actionType] ?? null;

        if ($actionClass === null) {
            return;
        }

        $schema = $actionClass::configSchema();

        $rules = [];
        foreach ($schema as $field => $fieldConfig) {
            if (! empty($fieldConfig['required'])) {
                $rules[$field] = 'required';
            }
        }

        if (empty($rules)) {
            return;
        }

        $validator = Validator::make($config, $rules);

        if ($validator->fails()) {
            throw new \RuntimeException(
                "Action '{$actionType}' config validation failed: " . $validator->errors()->first()
            );
        }
    }

    /**
     * Mark a workflow run as completed.
     */
    private function completeRun(WorkflowRun $run): void
    {
        $run->update([
            'status' => WorkflowRunStatus::Completed,
            'completed_at' => Carbon::now(),
        ]);

        WorkflowRunCompleted::dispatch($run);
    }

    /**
     * Mark a workflow run as failed with an error message.
     */
    private function failRun(WorkflowRun $run, string $errorMessage): WorkflowRun
    {
        $run->update([
            'status' => WorkflowRunStatus::Failed,
            'error_message' => $errorMessage,
            'completed_at' => Carbon::now(),
        ]);

        WorkflowRunFailed::dispatch($run);

        return $run;
    }
}
