<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Jobs\ExecuteWorkflowJob;
use Relaticle\Workflow\Models\Workflow;

it('completes a full workflow lifecycle via browser: load → save canvas → trigger', function () {
    Queue::fake();

    // Step 1: Create a workflow
    $workflow = Workflow::create([
        'name' => 'E2E Lifecycle Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
    ]);

    // Step 2: Load the empty canvas via browser
    $canvasUrl = "/workflow/api/workflows/{$workflow->id}/canvas";

    $this->visit($canvasUrl)
        ->assertSee('"nodes":[]')
        ->assertSee('"edges":[]');

    // Step 3: Save a workflow graph with trigger → action via browser fetch
    $savePayload = json_encode([
        'canvas_data' => ['zoom' => 1.0],
        'nodes' => [
            [
                'node_id' => 'trigger-1',
                'type' => 'trigger',
                'config' => [],
                'position_x' => 100,
                'position_y' => 100,
            ],
            [
                'node_id' => 'action-1',
                'type' => 'action',
                'action_type' => 'send_email',
                'config' => ['to' => 'user@example.com', 'subject' => 'Hello'],
                'position_x' => 300,
                'position_y' => 100,
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

    $escapedPayload = str_replace("'", "\\'", $savePayload);

    $saveResult = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$canvasUrl}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$escapedPayload}'}); return await r.json(); })()");

    expect($saveResult['message'])->toBe('Canvas saved successfully.');

    // Step 4: Verify nodes and edges were persisted
    $workflow->refresh();
    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);

    $triggerNode = $workflow->nodes->firstWhere('node_id', 'trigger-1');
    expect($triggerNode->type)->toBe(NodeType::Trigger);

    $actionNode = $workflow->nodes->firstWhere('node_id', 'action-1');
    expect($actionNode->type)->toBe(NodeType::Action);
    expect($actionNode->action_type)->toBe('send_email');

    // Step 5: Reload the canvas and verify saved state via browser
    $this->visit($canvasUrl)
        ->assertSee('trigger-1')
        ->assertSee('action-1')
        ->assertSee('send_email');

    // Step 6: Trigger the workflow via browser fetch POST
    $triggerUrl = "/workflow/api/workflows/{$workflow->id}/trigger";

    $triggerResult = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$triggerUrl}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'}}); return await r.json(); })()");

    expect($triggerResult['message'])->toBe('Workflow triggered successfully.');

    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) use ($workflow) {
        return $job->workflow->id === $workflow->id;
    });
});

it('completes a workflow lifecycle with condition branching via browser', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'E2E Condition Test',
        'trigger_type' => TriggerType::Manual,
        'is_active' => true,
    ]);

    $canvasUrl = "/workflow/api/workflows/{$workflow->id}/canvas";

    $savePayload = json_encode([
        'canvas_data' => ['zoom' => 1.0],
        'nodes' => [
            ['node_id' => 'trigger-1', 'type' => 'trigger', 'config' => [], 'position_x' => 100, 'position_y' => 200],
            ['node_id' => 'condition-1', 'type' => 'condition', 'config' => ['field' => 'status', 'operator' => 'equals', 'value' => 'active'], 'position_x' => 300, 'position_y' => 200],
            ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'yes@example.com'], 'position_x' => 500, 'position_y' => 100],
            ['node_id' => 'stop-1', 'type' => 'stop', 'config' => [], 'position_x' => 500, 'position_y' => 300],
        ],
        'edges' => [
            ['edge_id' => 'e1', 'source_node_id' => 'trigger-1', 'target_node_id' => 'condition-1'],
            ['edge_id' => 'e2', 'source_node_id' => 'condition-1', 'target_node_id' => 'action-1', 'condition_label' => 'yes'],
            ['edge_id' => 'e3', 'source_node_id' => 'condition-1', 'target_node_id' => 'stop-1', 'condition_label' => 'no'],
        ],
    ]);

    $escapedPayload = str_replace("'", "\\'", $savePayload);

    $saveResult = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$canvasUrl}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$escapedPayload}'}); return await r.json(); })()");

    expect($saveResult['message'])->toBe('Canvas saved successfully.');

    // Verify 4 nodes and 3 edges persisted
    $workflow->refresh();
    expect($workflow->nodes)->toHaveCount(4);
    expect($workflow->edges)->toHaveCount(3);

    $conditionNode = $workflow->nodes->firstWhere('node_id', 'condition-1');
    expect($conditionNode->type)->toBe(NodeType::Condition);

    $yesEdge = $workflow->edges->firstWhere('condition_label', 'yes');
    expect($yesEdge)->not->toBeNull();

    $noEdge = $workflow->edges->firstWhere('condition_label', 'no');
    expect($noEdge)->not->toBeNull();

    // Verify the graph loads correctly via browser
    $this->visit($canvasUrl)
        ->assertSee('condition-1')
        ->assertSee('yes')
        ->assertSee('no');
});

it('completes a webhook workflow lifecycle via browser', function () {
    Queue::fake();

    $workflow = Workflow::create([
        'name' => 'E2E Webhook Test',
        'trigger_type' => TriggerType::Webhook,
        'is_active' => true,
    ]);

    $canvasUrl = "/workflow/api/workflows/{$workflow->id}/canvas";

    $savePayload = json_encode([
        'canvas_data' => ['zoom' => 1.0],
        'nodes' => [
            ['node_id' => 'trigger-1', 'type' => 'trigger', 'config' => [], 'position_x' => 100, 'position_y' => 100],
            ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'http_request', 'config' => ['method' => 'POST', 'url' => 'https://api.example.com'], 'position_x' => 300, 'position_y' => 100],
        ],
        'edges' => [
            ['edge_id' => 'e1', 'source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
        ],
    ]);

    $escapedPayload = str_replace("'", "\\'", $savePayload);

    $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$canvasUrl}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$escapedPayload}'}); return await r.json(); })()");

    // Trigger via webhook
    $webhookUrl = "/workflow/api/webhooks/{$workflow->id}";

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$webhookUrl}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({event:'payment.success',amount:99.99})}); return await r.json(); })()");

    expect($result['message'])->toBe('Webhook processed successfully.');

    Queue::assertPushed(ExecuteWorkflowJob::class, function ($job) {
        return data_get($job->context, 'webhook.event') === 'payment.success'
            && data_get($job->context, 'webhook.amount') === 99.99;
    });
});

it('updates existing canvas via browser — modify and re-save', function () {
    $workflow = Workflow::create([
        'name' => 'E2E Update Canvas Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $canvasUrl = "/workflow/api/workflows/{$workflow->id}/canvas";

    // Initial save with 1 node
    $initial = json_encode([
        'canvas_data' => ['zoom' => 1.0],
        'nodes' => [
            ['node_id' => 'trigger-1', 'type' => 'trigger', 'config' => [], 'position_x' => 100, 'position_y' => 100],
        ],
        'edges' => [],
    ]);

    $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$canvasUrl}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$initial}'}); return await r.json(); })()");

    expect($workflow->nodes()->count())->toBe(1);

    // Update: add action node and edge
    $updated = json_encode([
        'canvas_data' => ['zoom' => 1.5],
        'nodes' => [
            ['node_id' => 'trigger-1', 'type' => 'trigger', 'config' => [], 'position_x' => 100, 'position_y' => 100],
            ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_webhook', 'config' => ['url' => 'https://hooks.example.com'], 'position_x' => 400, 'position_y' => 100],
        ],
        'edges' => [
            ['edge_id' => 'e1', 'source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
        ],
    ]);

    $escapedUpdated = str_replace("'", "\\'", $updated);

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$canvasUrl}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$escapedUpdated}'}); return await r.json(); })()");

    expect($result['message'])->toBe('Canvas saved successfully.');

    $workflow->refresh();
    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);
    expect($workflow->canvas_data['zoom'])->toBe(1.5);

    // Verify via browser GET
    $this->visit($canvasUrl)
        ->assertSee('action-1')
        ->assertSee('send_webhook');
});
