<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Services\FieldResolverService;

it('returns trigger record fields for a company workflow', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['entity_type' => 'companies', 'event' => 'record_created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'record_created', 'entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $actionNode = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => [],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $actionNode->id,
    ]);

    $service = app(FieldResolverService::class);
    $groups = $service->getAvailableFields($workflow->id, 'action-1');

    // Should have at least trigger group and built-in group
    expect($groups)->toBeArray();
    expect(count($groups))->toBeGreaterThanOrEqual(2);

    // Find the trigger record group
    $triggerGroup = collect($groups)->first(fn ($g) => str_starts_with($g['group'], 'Trigger Record'));
    expect($triggerGroup)->not->toBeNull();
    expect($triggerGroup['group'])->toContain('Companies');

    // Verify company standard fields are present
    $fieldKeys = collect($triggerGroup['fields'])->pluck('key')->all();
    expect($fieldKeys)->toContain('name');
    expect($fieldKeys)->toContain('phone');
    expect($fieldKeys)->toContain('address');

    // Verify each field has the expected structure
    $nameField = collect($triggerGroup['fields'])->firstWhere('key', 'name');
    expect($nameField)->toHaveKeys(['key', 'label', 'type', 'fullPath']);
    expect($nameField['fullPath'])->toBe('{{trigger.record.name}}');
});

it('returns upstream step output fields', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['entity_type' => 'companies', 'event' => 'record_created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'record_created', 'entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $findRecordNode = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'find_record',
        'config' => ['entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $sendEmailNode = $workflow->nodes()->create([
        'node_id' => 'action-2',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => [],
        'position_x' => 100,
        'position_y' => 400,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $findRecordNode->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-2',
        'source_node_id' => $findRecordNode->id,
        'target_node_id' => $sendEmailNode->id,
    ]);

    $service = app(FieldResolverService::class);
    $groups = $service->getAvailableFields($workflow->id, 'action-2');

    // Find the step group for find_record
    $stepGroup = collect($groups)->first(fn ($g) => str_starts_with($g['group'], 'Step: Find Record'));
    expect($stepGroup)->not->toBeNull();
    expect($stepGroup['group'])->toContain('action-1');

    // FindRecordAction outputSchema has: found, id, record (internal _entity_type is skipped)
    $fieldKeys = collect($stepGroup['fields'])->pluck('key')->all();
    expect($fieldKeys)->toContain('found');
    expect($fieldKeys)->toContain('id');
    expect($fieldKeys)->toContain('record');

    // Verify fullPath format
    $foundField = collect($stepGroup['fields'])->firstWhere('key', 'found');
    expect($foundField['fullPath'])->toBe('{{steps.action-1.output.found}}');
    expect($foundField['type'])->toBe('boolean');
});

it('includes loop context when node is inside a loop', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['entity_type' => 'companies', 'event' => 'record_created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'record_created', 'entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $loopNode = $workflow->nodes()->create([
        'node_id' => 'loop-1',
        'type' => NodeType::Loop,
        'action_type' => 'loop',
        'config' => ['collection' => 'trigger.record.items'],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $actionNode = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => [],
        'position_x' => 100,
        'position_y' => 400,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $loopNode->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-2',
        'source_node_id' => $loopNode->id,
        'target_node_id' => $actionNode->id,
    ]);

    $service = app(FieldResolverService::class);
    $groups = $service->getAvailableFields($workflow->id, 'action-1');

    // Find the loop context group
    $loopGroup = collect($groups)->firstWhere('group', 'Loop Context');
    expect($loopGroup)->not->toBeNull();

    $loopFieldKeys = collect($loopGroup['fields'])->pluck('key')->all();
    expect($loopFieldKeys)->toContain('item');
    expect($loopFieldKeys)->toContain('index');

    // Verify fullPath
    $itemField = collect($loopGroup['fields'])->firstWhere('key', 'item');
    expect($itemField['fullPath'])->toBe('{{loop.item}}');

    $indexField = collect($loopGroup['fields'])->firstWhere('key', 'index');
    expect($indexField['fullPath'])->toBe('{{loop.index}}');
});

it('always includes built-in variables', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => [],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $actionNode = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => [],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $actionNode->id,
    ]);

    $service = app(FieldResolverService::class);
    $groups = $service->getAvailableFields($workflow->id, 'action-1');

    // Built-in group should always be present
    $builtInGroup = collect($groups)->firstWhere('group', 'Built-in');
    expect($builtInGroup)->not->toBeNull();

    $builtInKeys = collect($builtInGroup['fields'])->pluck('key')->all();
    expect($builtInKeys)->toContain('now');
    expect($builtInKeys)->toContain('today');

    // Verify types
    $nowField = collect($builtInGroup['fields'])->firstWhere('key', 'now');
    expect($nowField['type'])->toBe('datetime');
    expect($nowField['fullPath'])->toBe('{{now}}');

    $todayField = collect($builtInGroup['fields'])->firstWhere('key', 'today');
    expect($todayField['type'])->toBe('date');
    expect($todayField['fullPath'])->toBe('{{today}}');
});

it('returns entity fields with correct structure', function () {
    $service = app(FieldResolverService::class);
    $fields = $service->getEntityFields('companies');

    expect($fields)->toBeArray();
    expect(count($fields))->toBeGreaterThanOrEqual(3);

    // Verify the name field
    $nameField = collect($fields)->firstWhere('key', 'name');
    expect($nameField)->not->toBeNull();
    expect($nameField)->toHaveKeys(['key', 'label', 'type', 'isCustom', 'group']);
    expect($nameField['isCustom'])->toBeFalse();
    expect($nameField['group'])->toBe('Standard Fields');
    expect($nameField['type'])->toBe('string');

    // Verify phone field exists
    $phoneField = collect($fields)->firstWhere('key', 'phone');
    expect($phoneField)->not->toBeNull();
    expect($phoneField['isCustom'])->toBeFalse();
    expect($phoneField['group'])->toBe('Standard Fields');

    // All standard fields should be grouped as "Standard Fields"
    $standardFields = collect($fields)->where('isCustom', false);
    $standardFields->each(function ($field) {
        expect($field['group'])->toBe('Standard Fields');
    });
});

it('returns upstream step nodes for step_node_id dropdowns', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['entity_type' => 'companies', 'event' => 'record_created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'record_created', 'entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $findRecordNode = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'find_record',
        'config' => ['entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $updateRecordNode = $workflow->nodes()->create([
        'node_id' => 'action-2',
        'type' => NodeType::Action,
        'action_type' => 'update_record',
        'config' => [],
        'position_x' => 100,
        'position_y' => 400,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $findRecordNode->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-2',
        'source_node_id' => $findRecordNode->id,
        'target_node_id' => $updateRecordNode->id,
    ]);

    $service = app(FieldResolverService::class);
    $stepNodes = $service->getUpstreamStepNodes($workflow->id, 'action-2');

    expect($stepNodes)->toBeArray();
    expect($stepNodes)->toHaveCount(1);

    $findRecordStep = $stepNodes[0];
    expect($findRecordStep)->toHaveKeys(['node_id', 'label', 'actionType']);
    expect($findRecordStep['node_id'])->toBe('action-1');
    expect($findRecordStep['label'])->toBe('Find Record');
    expect($findRecordStep['actionType'])->toBe('find_record');
});

it('returns empty array when node is not found', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::Manual,
        'trigger_config' => [],
    ]);

    $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => [],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    $service = app(FieldResolverService::class);

    $groups = $service->getAvailableFields($workflow->id, 'nonexistent-node');
    expect($groups)->toBe([]);

    $stepNodes = $service->getUpstreamStepNodes($workflow->id, 'nonexistent-node');
    expect($stepNodes)->toBe([]);
});

it('skips internal keys from action output schemas', function () {
    $workflow = Workflow::create([
        'name' => 'Test Workflow',
        'trigger_type' => TriggerType::RecordEvent,
        'trigger_config' => ['entity_type' => 'companies', 'event' => 'record_created'],
    ]);

    $triggerNode = $workflow->nodes()->create([
        'node_id' => 'trigger-1',
        'type' => NodeType::Trigger,
        'config' => ['event' => 'record_created', 'entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 100,
    ]);

    // FindRecordAction has _entity_type in its execute() output, but outputSchema()
    // only defines found, id, record. This test verifies the service uses outputSchema()
    // and would skip any _-prefixed keys if they were defined there.
    $findRecordNode = $workflow->nodes()->create([
        'node_id' => 'action-1',
        'type' => NodeType::Action,
        'action_type' => 'find_record',
        'config' => ['entity_type' => 'companies'],
        'position_x' => 100,
        'position_y' => 250,
    ]);

    $emailNode = $workflow->nodes()->create([
        'node_id' => 'action-2',
        'type' => NodeType::Action,
        'action_type' => 'send_email',
        'config' => [],
        'position_x' => 100,
        'position_y' => 400,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-1',
        'source_node_id' => $triggerNode->id,
        'target_node_id' => $findRecordNode->id,
    ]);

    $workflow->edges()->create([
        'edge_id' => 'edge-2',
        'source_node_id' => $findRecordNode->id,
        'target_node_id' => $emailNode->id,
    ]);

    $service = app(FieldResolverService::class);
    $groups = $service->getAvailableFields($workflow->id, 'action-2');

    $stepGroup = collect($groups)->first(fn ($g) => str_starts_with($g['group'], 'Step: Find Record'));
    expect($stepGroup)->not->toBeNull();

    // None of the field keys should start with underscore
    $fieldKeys = collect($stepGroup['fields'])->pluck('key')->all();
    foreach ($fieldKeys as $key) {
        expect(str_starts_with($key, '_'))->toBeFalse("Field key '{$key}' should not start with underscore");
    }
});
