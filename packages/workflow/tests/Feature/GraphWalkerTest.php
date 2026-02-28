<?php

declare(strict_types=1);

use Relaticle\Workflow\Engine\GraphWalker;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

beforeEach(function () {
    $this->workflow = Workflow::create([
        'name' => 'Graph Walker Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => 'App\\Models\\Company', 'event' => 'created'],
    ]);

    // Create nodes: Trigger -> Condition -> (yes) Action -> Stop
    //                                    -> (no) Stop2
    $this->triggerNode = $this->workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'created'],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $this->conditionNode = $this->workflow->nodes()->create([
        'node_id' => 'condition-1',
        'type' => NodeType::Condition,
        'config' => ['field' => 'record.status', 'operator' => 'equals', 'value' => 'active'],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $this->actionNode = $this->workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => ['to' => '{{record.email}}'],
        'position_x' => 0,
        'position_y' => 400,
    ]);

    $this->stopNode = $this->workflow->nodes()->create([
        'node_id' => 'stop-1',
        'type' => NodeType::Stop,
        'config' => [],
        'position_x' => 0,
        'position_y' => 550,
    ]);

    $this->stopNode2 = $this->workflow->nodes()->create([
        'node_id' => 'stop-2',
        'type' => NodeType::Stop,
        'config' => [],
        'position_x' => 200,
        'position_y' => 400,
    ]);

    // Edges
    $this->edgeTriggerToCondition = $this->workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $this->triggerNode->id,
        'target_node_id' => $this->conditionNode->id,
    ]);

    $this->edgeConditionYes = $this->workflow->edges()->create([
        'edge_id' => 'edge-2',
        'source_node_id' => $this->conditionNode->id,
        'target_node_id' => $this->actionNode->id,
        'condition_label' => 'yes',
        'condition_config' => ['branch' => 'true'],
    ]);

    $this->edgeConditionNo = $this->workflow->edges()->create([
        'edge_id' => 'edge-3',
        'source_node_id' => $this->conditionNode->id,
        'target_node_id' => $this->stopNode2->id,
        'condition_label' => 'no',
        'condition_config' => ['branch' => 'false'],
    ]);

    $this->edgeActionToStop = $this->workflow->edges()->create([
        'edge_id' => 'edge-4',
        'source_node_id' => $this->actionNode->id,
        'target_node_id' => $this->stopNode->id,
    ]);

    // Load fresh collections from database
    $this->workflow->refresh();
    $nodes = $this->workflow->nodes;
    $edges = $this->workflow->edges;

    $this->walker = new GraphWalker($nodes, $edges);
});

it('finds the trigger node', function () {
    $trigger = $this->walker->findTriggerNode();

    expect($trigger)->not->toBeNull();
    expect($trigger->id)->toBe($this->triggerNode->id);
    expect($trigger->type)->toBe(NodeType::Trigger);
});

it('returns null when no trigger node exists', function () {
    // Create a walker with only non-trigger nodes
    $nodesWithoutTrigger = $this->workflow->nodes->filter(
        fn ($node) => $node->type !== NodeType::Trigger
    );

    $walker = new GraphWalker($nodesWithoutTrigger, $this->workflow->edges);

    expect($walker->findTriggerNode())->toBeNull();
});

it('gets outgoing edges for a node', function () {
    $outgoing = $this->walker->getOutgoingEdges($this->triggerNode);

    expect($outgoing)->toHaveCount(1);
    expect($outgoing->first()->id)->toBe($this->edgeTriggerToCondition->id);
});

it('gets multiple outgoing edges for a condition node', function () {
    $outgoing = $this->walker->getOutgoingEdges($this->conditionNode);

    expect($outgoing)->toHaveCount(2);

    $edgeIds = $outgoing->pluck('id')->all();
    expect($edgeIds)->toContain($this->edgeConditionYes->id);
    expect($edgeIds)->toContain($this->edgeConditionNo->id);
});

it('returns empty collection for node with no outgoing edges', function () {
    $outgoing = $this->walker->getOutgoingEdges($this->stopNode);

    expect($outgoing)->toBeEmpty();
});

it('gets next nodes from a node', function () {
    $nextNodes = $this->walker->getNextNodes($this->triggerNode);

    expect($nextNodes)->toHaveCount(1);
    expect($nextNodes->first()->id)->toBe($this->conditionNode->id);
});

it('gets multiple next nodes from a condition node', function () {
    $nextNodes = $this->walker->getNextNodes($this->conditionNode);

    expect($nextNodes)->toHaveCount(2);

    $nodeIds = $nextNodes->pluck('id')->all();
    expect($nodeIds)->toContain($this->actionNode->id);
    expect($nodeIds)->toContain($this->stopNode2->id);
});

it('returns empty collection for terminal node next nodes', function () {
    $nextNodes = $this->walker->getNextNodes($this->stopNode);

    expect($nextNodes)->toBeEmpty();
});

it('gets edge by label from a condition node', function () {
    $yesEdge = $this->walker->getEdgeByLabel($this->conditionNode, 'yes');

    expect($yesEdge)->not->toBeNull();
    expect($yesEdge->id)->toBe($this->edgeConditionYes->id);
    expect($yesEdge->condition_label)->toBe('yes');
    expect($yesEdge->target_node_id)->toBe($this->actionNode->id);
});

it('gets the "no" edge by label from a condition node', function () {
    $noEdge = $this->walker->getEdgeByLabel($this->conditionNode, 'no');

    expect($noEdge)->not->toBeNull();
    expect($noEdge->id)->toBe($this->edgeConditionNo->id);
    expect($noEdge->condition_label)->toBe('no');
    expect($noEdge->target_node_id)->toBe($this->stopNode2->id);
});

it('returns null for non-existent edge label', function () {
    $edge = $this->walker->getEdgeByLabel($this->conditionNode, 'maybe');

    expect($edge)->toBeNull();
});

it('returns null for edge label on a node without labeled edges', function () {
    $edge = $this->walker->getEdgeByLabel($this->triggerNode, 'yes');

    expect($edge)->toBeNull();
});

it('identifies terminal nodes (stop nodes with no outgoing edges)', function () {
    expect($this->walker->isTerminal($this->stopNode))->toBeTrue();
    expect($this->walker->isTerminal($this->stopNode2))->toBeTrue();
});

it('identifies non-terminal nodes', function () {
    expect($this->walker->isTerminal($this->triggerNode))->toBeFalse();
    expect($this->walker->isTerminal($this->conditionNode))->toBeFalse();
    expect($this->walker->isTerminal($this->actionNode))->toBeFalse();
});
