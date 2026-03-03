<?php

declare(strict_types=1);

use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Services\TypedFieldResolver;

it('returns only date fields when type filter is date and datetime', function () {
    $workflow = Workflow::create([
        'name' => 'Typed Field Test',
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

    $resolver = app(TypedFieldResolver::class);
    $groups = $resolver->getFieldsByType($workflow->id, 'action-1', ['date', 'datetime']);

    expect($groups)->toBeArray();

    // Every field in every group must be date or datetime
    foreach ($groups as $group) {
        foreach ($group['fields'] as $field) {
            expect($field['type'])->toBeIn(['date', 'datetime']);
        }
    }

    // The built-in group has 'now' (datetime) and 'today' (date), so at least that group should be present
    $builtInGroup = collect($groups)->firstWhere('group', 'Built-in');
    expect($builtInGroup)->not->toBeNull();
    expect(count($builtInGroup['fields']))->toBe(2);
});

it('returns string and text fields when type filter is string and text', function () {
    $workflow = Workflow::create([
        'name' => 'Typed Field Test',
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

    $resolver = app(TypedFieldResolver::class);
    $groups = $resolver->getFieldsByType($workflow->id, 'action-1', ['string', 'text']);

    expect($groups)->toBeArray();

    // Every field in every group must be string or text
    foreach ($groups as $group) {
        foreach ($group['fields'] as $field) {
            expect($field['type'])->toBeIn(['string', 'text']);
        }
    }

    // Trigger record should have string fields like 'name', 'phone', 'address'
    $triggerGroup = collect($groups)->first(fn ($g) => str_starts_with($g['group'], 'Trigger Record'));
    expect($triggerGroup)->not->toBeNull();

    $fieldKeys = collect($triggerGroup['fields'])->pluck('key')->all();
    expect($fieldKeys)->toContain('name');
});

it('returns all fields when no type filter is provided', function () {
    $workflow = Workflow::create([
        'name' => 'Typed Field Test',
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

    $resolver = app(TypedFieldResolver::class);
    $allGroups = $resolver->getFieldsByType($workflow->id, 'action-1', null);

    // Should return same as FieldResolverService->getAvailableFields
    $fieldResolver = app(\Relaticle\Workflow\Services\FieldResolverService::class);
    $expectedGroups = $fieldResolver->getAvailableFields($workflow->id, 'action-1');

    expect($allGroups)->toBe($expectedGroups);
});
