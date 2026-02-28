<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

it('loads canvas data with nodes and edges via browser', function () {
    $workflow = Workflow::create([
        'name' => 'Canvas Browser Test',
        'trigger_type' => TriggerType::Manual,
        'canvas_data' => ['zoom' => 1.5],
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'position_x' => 100,
        'position_y' => 200,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'position_x' => 300,
        'position_y' => 200,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $page = $this->visit("/workflow/api/workflows/{$workflow->id}/canvas");

    $page->assertSee('canvas_data')
        ->assertSee('trigger-1')
        ->assertSee('action-1')
        ->assertSee('send_email')
        ->assertSee('"zoom":1.5');
});

it('returns meta with registered actions via browser', function () {
    $workflow = Workflow::create([
        'name' => 'Meta Browser Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/workflow/api/workflows/{$workflow->id}/canvas")
        ->assertSee('"meta"')
        ->assertSee('"actions"')
        ->assertSee('"models"');
});

it('returns 404 for non-existent workflow via browser', function () {
    $this->visit('/workflow/api/workflows/nonexistent-id/canvas')
        ->assertSee('404');
});

it('loads empty canvas for a new workflow via browser', function () {
    $workflow = Workflow::create([
        'name' => 'Empty Canvas Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $this->visit("/workflow/api/workflows/{$workflow->id}/canvas")
        ->assertSee('"nodes":[]')
        ->assertSee('"edges":[]');
});

it('returns node positions in canvas response via browser', function () {
    $workflow = Workflow::create([
        'name' => 'Position Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $workflow->nodes()->create([
        'node_id' => 'pos-node',
        'type' => NodeType::Trigger,
        'position_x' => 450,
        'position_y' => 300,
    ]);

    $this->visit("/workflow/api/workflows/{$workflow->id}/canvas")
        ->assertSee('"position_x":450')
        ->assertSee('"position_y":300');
});
