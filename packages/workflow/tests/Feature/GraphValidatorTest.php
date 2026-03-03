<?php

declare(strict_types=1);

use Relaticle\Workflow\Services\GraphValidator;

it('detects cycles in the graph', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => []],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
        ['source_node_id' => 'action-1', 'target_node_id' => 'action-2'],
        ['source_node_id' => 'action-2', 'target_node_id' => 'action-1'], // cycle!
    ];

    $result = $validator->validate($nodes, $edges);

    expect($result['errors'])->not->toBeEmpty();
    $cycleErrors = collect($result['errors'])->where('type', 'cycle');
    expect($cycleErrors)->not->toBeEmpty();
});

it('detects invalid connections (stop to action)', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => []],
        ['node_id' => 'stop-1', 'type' => 'stop', 'action_type' => null, 'config' => []],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'stop-1'],
        ['source_node_id' => 'stop-1', 'target_node_id' => 'action-1'], // invalid!
    ];

    $result = $validator->validate($nodes, $edges);

    $connectionErrors = collect($result['errors'])->where('type', 'invalid_connection');
    expect($connectionErrors)->not->toBeEmpty();
});

it('detects exceeded max outgoing edges', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => []],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-2'], // trigger max is 1!
    ];

    $result = $validator->validate($nodes, $edges);

    $maxErrors = collect($result['errors'])->where('type', 'max_outgoing_exceeded');
    expect($maxErrors)->not->toBeEmpty();
});

it('warns about disconnected nodes', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => []],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']], // no edges to/from
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
    ];

    $result = $validator->validate($nodes, $edges);

    $disconnected = collect($result['warnings'])->where('type', 'disconnected');
    expect($disconnected)->not->toBeEmpty();
    expect($disconnected->first()['nodeId'])->toBe('action-2');
});

it('warns about dead-end non-stop nodes', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => []],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
    ];

    $result = $validator->validate($nodes, $edges);

    $deadEnds = collect($result['warnings'])->where('type', 'dead_end');
    expect($deadEnds)->not->toBeEmpty();
});

it('detects missing required config fields', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => ['event' => 'record_created']],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => []], // missing to, subject, body
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
    ];

    $result = $validator->validate($nodes, $edges);

    $configErrors = collect($result['warnings'])->where('type', 'missing_required_config');
    expect($configErrors)->not->toBeEmpty();
    expect($configErrors->first()['nodeId'])->toBe('action-1');
});

it('passes validation for a valid graph', function () {
    $validator = app(GraphValidator::class);

    $nodes = [
        ['node_id' => 'trigger-1', 'type' => 'trigger', 'action_type' => null, 'config' => ['event' => 'record_created']],
        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['to' => 'a@b.c', 'subject' => 'x', 'body' => 'y']],
        ['node_id' => 'stop-1', 'type' => 'stop', 'action_type' => null, 'config' => []],
    ];
    $edges = [
        ['source_node_id' => 'trigger-1', 'target_node_id' => 'action-1'],
        ['source_node_id' => 'action-1', 'target_node_id' => 'stop-1'],
    ];

    $result = $validator->validate($nodes, $edges);

    expect($result['errors'])->toBeEmpty();
});
