<?php

declare(strict_types=1);

use Relaticle\Workflow\Services\BlockMetadataProvider;

it('returns block connection rules for all node types', function () {
    $provider = app(BlockMetadataProvider::class);
    $manifest = $provider->getManifest();

    expect($manifest)->toHaveKey('blocks');
    expect($manifest['blocks'])->toHaveKeys(['trigger', 'action', 'condition', 'delay', 'loop', 'stop']);

    // Trigger: root, 1 outgoing, no incoming
    $trigger = $manifest['blocks']['trigger'];
    expect($trigger['maxOutgoing'])->toBe(1);
    expect($trigger['maxIncoming'])->toBe(0);
    expect($trigger['isRoot'])->toBeTrue();
    expect($trigger['allowedTargets'])->toBe(['action', 'condition', 'delay', 'loop']);
    expect($trigger['allowedSources'])->toBe([]);

    // Stop: terminal, 0 outgoing
    $stop = $manifest['blocks']['stop'];
    expect($stop['maxOutgoing'])->toBe(0);
    expect($stop['isTerminal'])->toBeTrue();
    expect($stop['allowedTargets'])->toBe([]);

    // Condition: 2 outgoing with labels
    $condition = $manifest['blocks']['condition'];
    expect($condition['maxOutgoing'])->toBe(2);
    expect($condition['edgeLabels'])->toBe(['does match', 'does not match']);
});

it('returns action metadata with required configs', function () {
    $provider = app(BlockMetadataProvider::class);
    $manifest = $provider->getManifest();

    expect($manifest)->toHaveKey('actions');

    // Send email has required fields
    $sendEmail = $manifest['actions']['send_email'];
    expect($sendEmail['category'])->toBe('Communication');
    expect($sendEmail['requiredConfig'])->toContain('to', 'subject', 'body');

    // Create record inherits entity from trigger
    $createRecord = $manifest['actions']['create_record'];
    expect($createRecord['inheritsEntityFromTrigger'])->toBeTrue();
});

it('returns operator type compatibility map', function () {
    $provider = app(BlockMetadataProvider::class);
    $manifest = $provider->getManifest();

    expect($manifest)->toHaveKey('operators');
    expect($manifest['operators']['contains']['applicableTo'])->toBe(['string']);
    expect($manifest['operators']['greater_than']['applicableTo'])->toContain('number', 'date');
    expect($manifest['operators']['equals']['applicableTo'])->toContain('string', 'number', 'boolean', 'date');
});

it('returns available entity types', function () {
    $provider = app(BlockMetadataProvider::class);
    $manifest = $provider->getManifest();

    expect($manifest)->toHaveKey('entities');
    expect($manifest['entities'])->toContain('people', 'companies', 'opportunities', 'tasks', 'notes');
});
