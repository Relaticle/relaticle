<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

it('saves canvas data via browser fetch PUT', function () {
    $workflow = Workflow::create([
        'name' => 'Canvas Save Browser Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $url = "/workflow/api/workflows/{$workflow->id}/canvas";

    $payload = json_encode([
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

    // Escape single quotes in payload for JS
    $escapedPayload = str_replace("'", "\\'", $payload);

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$escapedPayload}'}); return await r.json(); })()");

    expect($result['message'])->toBe('Canvas saved successfully.');

    $workflow->refresh();
    expect($workflow->canvas_data)->toBe(['zoom' => 1, 'position' => [0, 0]]);
    expect($workflow->nodes)->toHaveCount(2);
    expect($workflow->edges)->toHaveCount(1);
});

it('syncs nodes on canvas save via browser — removes deleted nodes', function () {
    $workflow = Workflow::create([
        'name' => 'Sync Browser Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $workflow->nodes()->create(['node_id' => 'keep', 'type' => NodeType::Trigger]);
    $workflow->nodes()->create(['node_id' => 'remove', 'type' => NodeType::Action, 'action_type' => 'log']);

    $url = "/workflow/api/workflows/{$workflow->id}/canvas";
    $payload = json_encode([
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'keep', 'type' => 'trigger', 'config' => [], 'position_x' => 0, 'position_y' => 0],
        ],
        'edges' => [],
    ]);

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$payload}'}); return await r.json(); })()");

    expect($result['message'])->toBe('Canvas saved successfully.');
    expect($workflow->nodes()->count())->toBe(1);
    expect($workflow->nodes->first()->node_id)->toBe('keep');
});

it('validates required node type on canvas save via browser', function () {
    $workflow = Workflow::create([
        'name' => 'Validation Browser Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $url = "/workflow/api/workflows/{$workflow->id}/canvas";
    $payload = json_encode([
        'canvas_data' => [],
        'nodes' => [
            ['node_id' => 'n1', 'config' => [], 'position_x' => 0, 'position_y' => 0],
        ],
        'edges' => [],
    ]);

    $result = $this->visit('/_test')
        ->script("(async () => { const r = await fetch('{$url}', {method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:'{$payload}'}); return {status: r.status, body: await r.json()}; })()");

    expect($result['status'])->toBe(422);
});
