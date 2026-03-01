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
        return $this->getOutgoingEdges($node)->first(
            fn (WorkflowEdge $edge) => strcasecmp($edge->condition_label ?? '', $label) === 0
        );
    }

    /**
     * Find a node by its database ID within the loaded node set.
     */
    public function findNodeById(string $nodeId): ?WorkflowNode
    {
        return $this->nodes->first(fn (WorkflowNode $node) => $node->id === $nodeId);
    }

    /**
     * Determine if a node is terminal (has no outgoing edges).
     */
    public function isTerminal(WorkflowNode $node): bool
    {
        return $this->getOutgoingEdges($node)->isEmpty();
    }
}
