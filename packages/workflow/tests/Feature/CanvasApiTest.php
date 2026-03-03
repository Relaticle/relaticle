<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Facades\Workflow;
use Relaticle\Workflow\Models\Workflow as WorkflowModel;

it('saves canvas data and syncs nodes to database', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Canvas Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => ['zoom' => 1, 'position' => [0, 0]],
        'nodes' => [
            [
                'node_id' => 'trigger-1',
                'type' => 'trigger',
                'config' => [],
                'position_x' => 100,
                'position_y' => 200,
            ],
            [
                'node_id' => 'action-1',
                'type' => 'action',
                'action_type' => 'send_email',
                'config' => ['to' => 'test@example.com'],
                'position_x' => 300,
                'position_y' => 200,
            ],
        ],
        'edges' => [
            [
                'edge_id' => 'e1',
                'source_node_id' => 'trigger-1',
                'target_node_id' => 'action-1',
            ],
        ],
    ]);

    $response->assertOk();

    $workflow->refresh();
    expect($workflow->canvas_data)->toBe(['zoom' => 1, 'position' => [0, 0]]);
    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);

    $triggerNode = $workflow->nodes->firstWhere('node_id', 'trigger-1');
    expect($triggerNode->type)->toBe(NodeType::Trigger);
    expect($triggerNode->position_x)->toBe(100);
});

it('loads canvas data with nodes and edges', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Load Test',
        'trigger_type' => TriggerType::Manual,
        'canvas_data' => ['zoom' => 1.5],
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 't1',
        'type' => NodeType::Trigger,
        'position_x' => 50,
        'position_y' => 50,
    ]);
    $action = $workflow->nodes()->create([
        'node_id' => 'a1',
        'type' => NodeType::Action,
        'action_type' => 'log_message',
        'position_x' => 250,
        'position_y' => 50,
    ]);
    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/canvas");

    $response->assertOk();
    $data = $response->json();

    expect($data['canvas_data'])->toBe(['zoom' => 1.5]);
    expect($data['nodes'])->toHaveCount(2);
    expect($data['edges'])->toHaveCount(1);
});

it('syncs nodes on save — removes deleted nodes', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Sync Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    // Create initial nodes
    $workflow->nodes()->create(['node_id' => 'keep', 'type' => NodeType::Trigger]);
    $workflow->nodes()->create(['node_id' => 'remove', 'type' => NodeType::Action, 'action_type' => 'log']);

    // Save with only 'keep' node — 'remove' should be deleted
    $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'keep', 'type' => 'trigger', 'config' => [], 'position_x' => 0, 'position_y' => 0],
        ],
        'edges' => [],
    ]);

    expect($workflow->nodes()->count())->toBe(1);
    expect($workflow->nodes->first()->node_id)->toBe('keep');
});

it('returns registered models and actions for sidebar', function () {
    Workflow::registerTriggerableModel(
        \Relaticle\Workflow\Tests\Fixtures\TestCompany::class,
        [
            'label' => 'Company',
            'events' => ['created', 'updated'],
            'fields' => fn () => ['name' => ['type' => 'string', 'label' => 'Name']],
        ]
    );

    $workflow = WorkflowModel::create([
        'name' => 'Meta Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/canvas");

    $response->assertOk();
    $data = $response->json();

    expect($data['meta']['models'])->toBeArray();
    expect($data['meta']['actions'])->toBeArray();
    // Should include registered models and built-in actions
});

it('returns 404 for non-existent workflow', function () {
    $response = $this->getJson('/workflow/api/workflows/nonexistent/canvas');
    $response->assertNotFound();
});

it('rejects canvas save with stale version', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Version Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [],
        'edges' => [],
        'canvas_data' => [],
        'canvas_version' => 0,
    ]);

    $response->assertStatus(409);
    $response->assertJson(['error' => 'Canvas has been modified by another user. Please refresh and try again.']);
});

it('increments canvas_version on successful save', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Version Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [],
        'edges' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);

    $response->assertOk();
    expect($workflow->fresh()->canvas_version)->toBe(2);
});

it('rejects concurrent canvas saves with version conflict inside transaction', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Concurrent Save Test',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);

    // First save with version 1 — succeeds
    $response1 = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [['node_id' => 'trigger-1', 'type' => 'trigger', 'config' => [], 'position_x' => 0, 'position_y' => 0]],
        'edges' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);
    $response1->assertOk();
    expect($workflow->fresh()->canvas_version)->toBe(2);

    // Second save with stale version 1 — must be rejected
    $response2 = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'nodes' => [['node_id' => 'trigger-1', 'type' => 'trigger', 'config' => [], 'position_x' => 100, 'position_y' => 100]],
        'edges' => [],
        'canvas_data' => [],
        'canvas_version' => 1,
    ]);
    $response2->assertStatus(409);
});

it('validates required node type on save', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Validation Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'n1', 'config' => [], 'position_x' => 0, 'position_y' => 0],
            // Missing 'type'
        ],
        'edges' => [],
    ]);

    $response->assertStatus(422);
});

it('rejects edges referencing non-existent source node', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Edge Validation Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 0, 'position_y' => 0],
        ],
        'edges' => [
            ['edge_id' => 'e1', 'source_node_id' => 'nonexistent-node', 'target_node_id' => 'trigger-1'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'error' => "Edge 'e1' references non-existent source node 'nonexistent-node'.",
    ]);
});

it('rejects edges referencing non-existent target node', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Edge Validation Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 0, 'position_y' => 0],
        ],
        'edges' => [
            ['edge_id' => 'e1', 'source_node_id' => 'trigger-1', 'target_node_id' => 'nonexistent-node'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'error' => "Edge 'e1' references non-existent target node 'nonexistent-node'.",
    ]);
});

it('rejects nodes with invalid type', function () {
    $workflow = WorkflowModel::create([
        'name' => 'Type Validation Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'node-1', 'type' => 'invalid_type', 'position_x' => 0, 'position_y' => 0],
        ],
        'edges' => [],
    ]);

    $response->assertStatus(422);
});

it('accepts all valid node types', function () {
    $workflow = WorkflowModel::create([
        'name' => 'All Types Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->putJson("/workflow/api/workflows/{$workflow->id}/canvas", [
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'n1', 'type' => 'trigger', 'position_x' => 0, 'position_y' => 0],
            ['node_id' => 'n2', 'type' => 'action', 'action_type' => 'send_email', 'position_x' => 100, 'position_y' => 0],
            ['node_id' => 'n3', 'type' => 'condition', 'position_x' => 200, 'position_y' => 0],
            ['node_id' => 'n4', 'type' => 'delay', 'position_x' => 300, 'position_y' => 0],
            ['node_id' => 'n5', 'type' => 'loop', 'position_x' => 400, 'position_y' => 0],
            ['node_id' => 'n6', 'type' => 'stop', 'position_x' => 500, 'position_y' => 0],
        ],
        'edges' => [
            ['edge_id' => 'e1', 'source_node_id' => 'n1', 'target_node_id' => 'n2'],
            ['edge_id' => 'e2', 'source_node_id' => 'n2', 'target_node_id' => 'n3'],
        ],
    ]);

    $response->assertOk();
    expect($workflow->fresh()->nodes()->count())->toBe(6);
});
