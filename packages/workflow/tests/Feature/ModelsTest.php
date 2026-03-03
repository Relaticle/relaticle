<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\StepStatus;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowEdge;
use Relaticle\Workflow\Models\WorkflowNode;
use Relaticle\Workflow\Models\WorkflowRun;
use Relaticle\Workflow\Models\WorkflowRunStep;

it('creates a workflow with nodes and edges', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => 'App\\Models\\Company', 'event' => 'created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'node-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'created'],
        'position_x' => 100,
        'position_y' => 200,
    ]);

    $actionNode = $workflow->nodes()->create([
        'node_id' => 'node-2',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => ['to' => '{{record.email}}'],
        'position_x' => 100,
        'position_y' => 400,
    ]);

    $edge = $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $actionNode->id,
    ]);

    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);
    expect($edge->sourceNode->id)->toBe($triggerNode->id);
    expect($edge->targetNode->id)->toBe($actionNode->id);
});

it('creates a workflow run with steps', function () {
    $workflow = Workflow::create([
        'name' => 'Run Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $node = $workflow->nodes()->create([
        'node_id' => 'node-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $run = $workflow->runs()->create([
        'status' => WorkflowRunStatus::Running,
        'started_at' => now(),
        'context_data' => ['record' => ['name' => 'Acme']],
    ]);

    $step = $run->steps()->create([
        'workflow_node_id' => $node->id,
        'status' => StepStatus::Completed,
        'input_data' => ['to' => 'test@example.com'],
        'output_data' => ['sent' => true],
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    expect($run->steps)->toHaveCount(1);
    expect($step->node->id)->toBe($node->id);
    expect($step->run->id)->toBe($run->id);
    expect($run->workflow->id)->toBe($workflow->id);
});

it('soft deletes a workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Deletable',
        'trigger_type' => TriggerType::Manual,
    ]);

    $workflow->delete();

    expect(Workflow::count())->toBe(0);
    expect(Workflow::withTrashed()->count())->toBe(1);
});

it('casts trigger_type to TriggerType enum', function () {
    $workflow = Workflow::create([
        'name' => 'Cast Test',
        'trigger_type' => TriggerType::Webhook,
    ]);

    $workflow->refresh();

    expect($workflow->trigger_type)->toBe(TriggerType::Webhook);
});

it('casts config and canvas_data as arrays', function () {
    $workflow = Workflow::create([
        'name' => 'JSON Test',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['model' => 'App\\Models\\Task'],
        'canvas_data' => ['nodes' => [], 'edges' => []],
    ]);

    $workflow->refresh();

    expect($workflow->trigger_config)->toBeArray();
    expect($workflow->trigger_config['model'])->toBe('App\\Models\\Task');
    expect($workflow->canvas_data)->toBeArray();
});

it('reports activation errors for empty workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Empty', 'trigger_type' => TriggerType::Manual,
        'trigger_config' => [], 'canvas_data' => [],
    ]);
    expect($workflow->canActivate())->toBeFalse();
    expect($workflow->getActivationErrors())->toContain('Workflow must have exactly one trigger node.');
});

it('allows activation for valid workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Valid', 'trigger_type' => TriggerType::Manual,
        'trigger_config' => [], 'canvas_data' => [],
    ]);
    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1', 'type' => \Relaticle\Workflow\Enums\NodeType::Trigger,
        'position_x' => 0, 'position_y' => 0,
    ]);
    $workflow->nodes()->create([
        'node_id' => 'action-1', 'type' => \Relaticle\Workflow\Enums\NodeType::Action,
        'action_type' => 'log_message', 'config' => [],
        'position_x' => 0, 'position_y' => 100,
    ]);
    expect($workflow->canActivate())->toBeTrue();
});
