<?php

declare(strict_types=1);

use Laravel\Ai\AnonymousAgent;
use Relaticle\Workflow\Actions\SummarizeAction;

it('summarizes a trigger record using AI', function () {
    AnonymousAgent::fake(['John Doe is an active user with the email john@example.com.']);

    $action = new SummarizeAction();

    $config = [
        'record_source' => 'trigger',
        'fields' => ['name', 'email', 'status'],
    ];

    $context = [
        'trigger' => [
            'record' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'status' => 'active',
                'internal_id' => 123,
            ],
        ],
    ];

    $result = $action->execute($config, $context);

    expect($result)->toBeArray();
    expect($result['summary'])->toBeString();
    expect($result['summary'])->not->toBeEmpty();
    expect($result['field_count'])->toBe(3);
});

it('returns error when record cannot be resolved', function () {
    $action = new SummarizeAction();

    $config = ['record_source' => 'trigger'];
    $context = [];

    $result = $action->execute($config, $context);

    expect($result['error'])->toBe('Could not resolve record to summarize');
    expect($result['summary'])->toBeNull();
});

it('uses all scalar fields when no fields specified', function () {
    AnonymousAgent::fake(['Acme Corp is based in SF.']);

    $action = new SummarizeAction();

    $config = ['record_source' => 'trigger', 'fields' => []];
    $context = [
        'trigger' => [
            'record' => ['name' => 'Acme', 'city' => 'SF'],
        ],
    ];

    $result = $action->execute($config, $context);

    expect($result)->toBeArray();
    expect($result['summary'])->toBeString();
    expect($result['field_count'])->toBe(2);
});

it('falls back to field text when AI fails', function () {
    AnonymousAgent::fake([fn () => throw new \RuntimeException('API error')]);

    $action = new SummarizeAction();

    $config = [
        'record_source' => 'trigger',
        'fields' => ['name', 'email'],
    ];
    $context = [
        'trigger' => [
            'record' => ['name' => 'Jane', 'email' => 'jane@test.com'],
        ],
    ];

    $result = $action->execute($config, $context);

    expect($result['summary'])->toContain('Jane');
    expect($result['summary'])->toContain('jane@test.com');
    expect($result['ai_error'])->toContain('API error');
    expect($result['field_count'])->toBe(2);
});

it('resolves record from a previous step output', function () {
    AnonymousAgent::fake(['Step record summary.']);

    $action = new SummarizeAction();

    $config = [
        'record_source' => 'step',
        'step_node_id' => 'action-1',
        'fields' => ['company_name'],
    ];
    $context = [
        'steps' => [
            'action-1' => [
                'output' => [
                    'record' => ['company_name' => 'Tech Corp'],
                ],
            ],
        ],
    ];

    $result = $action->execute($config, $context);

    expect($result['summary'])->toBeString();
    expect($result['field_count'])->toBe(1);
});
