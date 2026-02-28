<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Engine;

use Illuminate\Support\Carbon;
use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Events\WorkflowRunCompleted;
use Relaticle\Workflow\Events\WorkflowRunFailed;
use Relaticle\Workflow\Events\WorkflowTriggered;
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
            $workflow->load(['nodes', 'edges']);

            $walker = new GraphWalker($workflow->nodes, $workflow->edges);
            $triggerNode = $walker->findTriggerNode();

            if ($triggerNode === null) {
                return $this->failRun($run, 'No trigger node found in workflow.');
            }

            $this->walkGraph($walker, $triggerNode, $run, $context);

            $this->completeRun($run);
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

        while (! $queue->isEmpty()) {
            /** @var WorkflowNode $currentNode */
            $currentNode = $queue->dequeue();

            // Skip already-processed nodes to avoid cycles
            if (in_array($currentNode->id, $processedNodeIds, true)) {
                continue;
            }

            $processedNodeIds[] = $currentNode->id;

            match ($currentNode->type) {
                NodeType::Action => $this->executeActionNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Condition => $this->executeConditionNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Delay => $this->executeDelayNode($currentNode, $walker, $run, $context, $queue),
                NodeType::Stop => null, // Stop node: do nothing, just don't enqueue further nodes
                default => $this->enqueueNextNodes($walker, $currentNode, $queue),
            };
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
     * Execute a delay node: run the built-in DelayAction, record the step, then continue.
     *
     * @param  \SplQueue<WorkflowNode>  $queue
     * @param  array<string, mixed>  $context
     */
    private function executeDelayNode(
        WorkflowNode $node,
        GraphWalker $walker,
        WorkflowRun $run,
        array $context,
        \SplQueue $queue,
    ): void {
        $step = $this->createStep($run, $node, StepStatus::Running);

        $resolvedConfig = $this->variableResolver->resolveArray($node->config ?? [], $context);

        $step->update([
            'input_data' => $resolvedConfig,
            'started_at' => Carbon::now(),
        ]);

        $action = new \Relaticle\Workflow\Actions\DelayAction();
        $output = $action->execute($resolvedConfig, $context);

        $step->update([
            'status' => StepStatus::Completed,
            'output_data' => $output,
            'completed_at' => Carbon::now(),
        ]);

        $this->enqueueNextNodes($walker, $node, $queue);
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
        // The walker holds the collection of nodes. We need to access them.
        // Since GraphWalker uses getNextNodes which filters by target_node_id,
        // we can use the workflow's loaded nodes directly.
        // However, we need a way to find by ID. Let's use the reflection approach
        // or simply query the model.
        return WorkflowNode::find($nodeId);
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
