<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowTemplate;
use Relaticle\Workflow\Services\WorkflowTemplateService;

it('creates a workflow template with definition', function () {
    $template = WorkflowTemplate::create([
        'name' => 'Test Template',
        'description' => 'A test template',
        'category' => 'Testing',
        'icon' => 'test',
        'definition' => [
            'trigger_type' => 'manual',
            'nodes' => [
                ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Hello'], 'position_x' => 400, 'position_y' => 260],
            ],
            'edges' => [
                ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ],
    ]);

    $template->refresh();

    expect($template->name)->toBe('Test Template');
    expect($template->category)->toBe('Testing');
    expect($template->is_active)->toBeTrue();
    expect($template->definition)->toBeArray();
    expect($template->definition['nodes'])->toHaveCount(2);
    expect($template->definition['edges'])->toHaveCount(1);
});

it('scopes active templates', function () {
    WorkflowTemplate::create([
        'name' => 'Active Template',
        'category' => 'Test',
        'definition' => ['trigger_type' => 'manual', 'nodes' => [], 'edges' => []],
        'is_active' => true,
    ]);

    WorkflowTemplate::create([
        'name' => 'Inactive Template',
        'category' => 'Test',
        'definition' => ['trigger_type' => 'manual', 'nodes' => [], 'edges' => []],
        'is_active' => false,
    ]);

    expect(WorkflowTemplate::active()->count())->toBe(1);
    expect(WorkflowTemplate::active()->first()->name)->toBe('Active Template');
});

it('creates a workflow from template via service', function () {
    $template = WorkflowTemplate::create([
        'name' => 'Service Test Template',
        'description' => 'From template',
        'category' => 'Test',
        'definition' => [
            'trigger_type' => 'record_event',
            'trigger_config' => ['event' => 'created'],
            'nodes' => [
                ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Welcome'], 'position_x' => 400, 'position_y' => 260],
            ],
            'edges' => [
                ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ],
    ]);

    $service = new WorkflowTemplateService();
    $workflow = $service->createFromTemplate($template, 'tenant-123', 'user-456');

    expect($workflow)->toBeInstanceOf(Workflow::class);
    expect($workflow->name)->toBe('Service Test Template');
    expect($workflow->description)->toBe('From template');
    expect($workflow->status)->toBe(WorkflowStatus::Draft);
    expect($workflow->tenant_id)->toBe('tenant-123');
    expect($workflow->creator_id)->toBe('user-456');
    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);

    // Verify nodes were created with correct types
    $triggerNode = $workflow->nodes->firstWhere('node_id', 'trigger-1');
    expect($triggerNode->type->value)->toBe('trigger');

    $actionNode = $workflow->nodes->firstWhere('node_id', 'action-1');
    expect($actionNode->type->value)->toBe('action');
    expect($actionNode->action_type)->toBe('send_email');
    expect($actionNode->config)->toBe(['subject' => 'Welcome']);
});

it('creates a workflow from template with name override', function () {
    $template = WorkflowTemplate::create([
        'name' => 'Original Name',
        'description' => 'Template desc',
        'category' => 'Test',
        'definition' => [
            'trigger_type' => 'manual',
            'nodes' => [
                ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
            ],
            'edges' => [],
        ],
    ]);

    $service = new WorkflowTemplateService();
    $workflow = $service->createFromTemplate($template, 'tenant-1', null, ['name' => 'Custom Name']);

    expect($workflow->name)->toBe('Custom Name');
    expect($workflow->description)->toBe('Template desc');
});

it('resolves edge references to DB node IDs', function () {
    $template = WorkflowTemplate::create([
        'name' => 'Edge Resolution Test',
        'category' => 'Test',
        'definition' => [
            'trigger_type' => 'manual',
            'nodes' => [
                ['node_id' => 't1', 'type' => 'trigger', 'position_x' => 0, 'position_y' => 0],
                ['node_id' => 'a1', 'type' => 'action', 'action_type' => 'send_email', 'position_x' => 0, 'position_y' => 200],
            ],
            'edges' => [
                ['edge_id' => 'e1', 'source' => 't1', 'target' => 'a1'],
            ],
        ],
    ]);

    $service = new WorkflowTemplateService();
    $workflow = $service->createFromTemplate($template, 'tenant-1');

    $workflow->load(['nodes', 'edges']);

    $edge = $workflow->edges->first();
    $triggerNode = $workflow->nodes->firstWhere('node_id', 't1');
    $actionNode = $workflow->nodes->firstWhere('node_id', 'a1');

    expect($edge->source_node_id)->toBe($triggerNode->id);
    expect($edge->target_node_id)->toBe($actionNode->id);
});

it('skips edges with invalid node references', function () {
    $template = WorkflowTemplate::create([
        'name' => 'Bad Edge Test',
        'category' => 'Test',
        'definition' => [
            'trigger_type' => 'manual',
            'nodes' => [
                ['node_id' => 't1', 'type' => 'trigger', 'position_x' => 0, 'position_y' => 0],
            ],
            'edges' => [
                ['edge_id' => 'e1', 'source' => 't1', 'target' => 'nonexistent'],
            ],
        ],
    ]);

    $service = new WorkflowTemplateService();
    $workflow = $service->createFromTemplate($template, 'tenant-1');

    $workflow->load('edges');

    expect($workflow->edges)->toHaveCount(0);
});

it('preserves condition labels on template edges', function () {
    $template = WorkflowTemplate::create([
        'name' => 'Condition Edge Test',
        'category' => 'Test',
        'definition' => [
            'trigger_type' => 'manual',
            'nodes' => [
                ['node_id' => 't1', 'type' => 'trigger', 'position_x' => 0, 'position_y' => 0],
                ['node_id' => 'c1', 'type' => 'condition', 'position_x' => 0, 'position_y' => 200],
                ['node_id' => 'a1', 'type' => 'action', 'action_type' => 'send_email', 'position_x' => 0, 'position_y' => 400],
            ],
            'edges' => [
                ['edge_id' => 'e1', 'source' => 't1', 'target' => 'c1'],
                ['edge_id' => 'e2', 'source' => 'c1', 'target' => 'a1', 'condition_label' => 'Yes'],
            ],
        ],
    ]);

    $service = new WorkflowTemplateService();
    $workflow = $service->createFromTemplate($template, 'tenant-1');
    $workflow->load('edges');

    $conditionEdge = $workflow->edges->firstWhere('edge_id', 'e2');
    expect($conditionEdge->condition_label)->toBe('Yes');
});
