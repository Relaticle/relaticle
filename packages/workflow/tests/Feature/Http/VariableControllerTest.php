<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;

it('returns trigger record fields', function () {
    $workflow = Workflow::create([
        'name' => 'Variable Test',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['entity_type' => 'companies'],
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/variables?node_id=action-1");

    $response->assertOk();
    $data = $response->json();

    expect($data['groups'])->toBeArray();

    // Should have trigger record fields group
    $triggerGroup = collect($data['groups'])->firstWhere('prefix', 'trigger.record');
    expect($triggerGroup)->not->toBeNull();
    expect($triggerGroup['label'])->toContain('Companies');

    $fieldPaths = collect($triggerGroup['fields'])->pluck('path')->toArray();
    expect($fieldPaths)->toContain('trigger.record.name');
});

it('returns upstream step outputs', function () {
    $workflow = Workflow::create([
        'name' => 'Step Output Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action1 = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $action2 = $workflow->nodes()->create([
        'node_id' => 'action-2',
        'type' => NodeType::Action,
        'action_type' => 'send_webhook',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action1->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e2',
        'source_node_id' => $action1->id,
        'target_node_id' => $action2->id,
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/variables?node_id=action-2");

    $response->assertOk();
    $groups = $response->json('groups');

    // Should include step output from action-1 (send_email)
    $stepGroup = collect($groups)->first(fn ($g) => str_contains($g['prefix'], 'action-1'));
    expect($stepGroup)->not->toBeNull();
    expect($stepGroup['prefix'])->toBe('steps.action-1.output');
});

it('excludes downstream step outputs', function () {
    $workflow = Workflow::create([
        'name' => 'Exclude Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action1 = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $action2 = $workflow->nodes()->create([
        'node_id' => 'action-2',
        'type' => NodeType::Action,
        'action_type' => 'send_webhook',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action1->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e2',
        'source_node_id' => $action1->id,
        'target_node_id' => $action2->id,
    ]);

    // Request variables for action-1 — action-2 is downstream, should not appear
    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/variables?node_id=action-1");

    $response->assertOk();
    $groups = $response->json('groups');

    $stepGroup = collect($groups)->first(fn ($g) => str_contains($g['prefix'] ?? '', 'action-2'));
    expect($stepGroup)->toBeNull();
});

it('returns built-in variables', function () {
    $workflow = Workflow::create([
        'name' => 'Built-in Test',
        'trigger_type' => TriggerType::Manual,
    ]);

    $trigger = $workflow->nodes()->create([
        'node_id' => 'trigger',
        'type' => NodeType::Trigger,
    ]);

    $action = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
    ]);

    $workflow->edges()->create([
        'edge_id' => 'e1',
        'source_node_id' => $trigger->id,
        'target_node_id' => $action->id,
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/variables?node_id=action-1");

    $response->assertOk();
    $groups = $response->json('groups');

    $builtinGroup = collect($groups)->firstWhere('label', 'Built-in');
    expect($builtinGroup)->not->toBeNull();

    $paths = collect($builtinGroup['fields'])->pluck('path')->toArray();
    expect($paths)->toContain('now');
    expect($paths)->toContain('today');
});

it('returns 422 when node_id is missing', function () {
    $workflow = Workflow::create([
        'name' => 'Missing Node ID',
        'trigger_type' => TriggerType::Manual,
    ]);

    $response = $this->getJson("/workflow/api/workflows/{$workflow->id}/variables");

    $response->assertStatus(422);
});
