<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Engine;

use Illuminate\Support\Collection;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Models\WorkflowEdge;
use Relaticle\Workflow\Models\WorkflowNode;

class GraphWalker
{
    /**
     * @param  Collection<int, WorkflowNode>  $nodes
     * @param  Collection<int, WorkflowEdge>  $edges
     */
    public function __construct(
        private readonly Collection $nodes,
        private readonly Collection $edges,
    ) {}

    /**
     * Find the trigger node in the workflow graph.
     */
    public function findTriggerNode(): ?WorkflowNode
    {
        return $this->nodes->first(fn (WorkflowNode $node) => $node->type === NodeType::Trigger);
    }

    /**
     * Get all outgoing edges from a given node.
     *
     * @return Collection<int, WorkflowEdge>
     */
    public function getOutgoingEdges(WorkflowNode $node): Collection
    {
        return $this->edges->filter(fn (WorkflowEdge $edge) => $edge->source_node_id === $node->id)->values();
    }

    /**
     * Get all target nodes reachable from a given node via outgoing edges.
     *
     * @return Collection<int, WorkflowNode>
     */
    public function getNextNodes(WorkflowNode $node): Collection
    {
        $outgoing = $this->getOutgoingEdges($node);

        return $outgoing
            ->map(fn (WorkflowEdge $edge) => $this->nodes->first(
                fn (WorkflowNode $n) => $n->id === $edge->target_node_id
            ))
            ->filter()
            ->values();
    }

    /**
     * Get the outgoing edge from a node that matches a specific condition label.
     */
    public function getEdgeByLabel(WorkflowNode $node, string $label): ?WorkflowEdge
    {
        // Support both legacy ("yes"/"no") and Attio-style ("does match"/"does not match") labels
        $aliases = match (strtolower($label)) {
            'yes', 'does match' => ['yes', 'does match'],
            'no', 'does not match' => ['no', 'does not match'],
            default => [$label],
        };

        return $this->getOutgoingEdges($node)->first(
            fn (WorkflowEdge $edge) => in_array(
                strtolower($edge->condition_label ?? ''),
                $aliases,
                true
            )
        );
    }

    /**
     * Get all incoming edges to a given node.
     *
     * @return Collection<int, WorkflowEdge>
     */
    public function getIncomingEdges(WorkflowNode $node): Collection
    {
        return $this->edges->filter(fn (WorkflowEdge $edge) => $edge->target_node_id === $node->id)->values();
    }

    /**
     * Find a node by its database ID within the loaded node set.
     */
    public function findNodeById(string $nodeId): ?WorkflowNode
    {
        return $this->nodes->first(fn (WorkflowNode $node) => $node->id === $nodeId);
    }

    /**
     * Find a node by its canvas node_id (e.g. "action-2"), not the database ULID.
     */
    public function findNodeByNodeId(string $nodeId): ?WorkflowNode
    {
        return $this->nodes->first(fn (WorkflowNode $node) => $node->node_id === $nodeId);
    }

    /**
     * Get all predecessor (upstream) nodes reachable by walking edges backwards from the given node.
     *
     * @return Collection<int, WorkflowNode>
     */
    public function getPredecessors(WorkflowNode $targetNode): Collection
    {
        $predecessors = collect();
        $visited = [];
        $queue = new \SplQueue();
        $queue->enqueue($targetNode);

        while (!$queue->isEmpty()) {
            $current = $queue->dequeue();

            // Find all edges that point TO this node
            $incomingEdges = $this->edges->filter(
                fn (WorkflowEdge $edge) => $edge->target_node_id === $current->id
            );

            foreach ($incomingEdges as $edge) {
                $sourceNode = $this->findNodeById($edge->source_node_id);
                if ($sourceNode && !in_array($sourceNode->id, $visited, true)) {
                    $visited[] = $sourceNode->id;
                    $predecessors->push($sourceNode);
                    $queue->enqueue($sourceNode);
                }
            }
        }

        return $predecessors;
    }

    /**
     * Determine if a node is terminal (has no outgoing edges).
     */
    public function isTerminal(WorkflowNode $node): bool
    {
        return $this->getOutgoingEdges($node)->isEmpty();
    }
}
