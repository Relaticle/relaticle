<?php

declare(strict_types=1);

use Relaticle\Workflow\Engine\VariableResolver;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

it('resolves trigger record standard field from eloquent model', function () {
    $company = new TestCompany(['name' => 'Acme Corp']);

    $resolver = new VariableResolver();
    $context = [
        'trigger' => [
            'record' => $company,
            'event' => 'created',
        ],
    ];

    expect($resolver->resolve('{{trigger.record.name}}', $context))->toBe('Acme Corp');
});

it('resolves trigger event from context', function () {
    $company = new TestCompany(['name' => 'Test']);

    $resolver = new VariableResolver();
    $context = [
        'trigger' => [
            'record' => $company,
            'event' => 'created',
        ],
    ];

    expect($resolver->resolve('{{trigger.event}}', $context))->toBe('created');
});

it('resolves step output values', function () {
    $resolver = new VariableResolver();
    $context = [
        'steps' => [
            'action-2' => [
                'output' => ['sent' => true, 'to' => 'user@example.com'],
                'status' => 'completed',
            ],
        ],
    ];

    expect($resolver->resolve('{{steps.action-2.output.to}}', $context))->toBe('user@example.com');
    expect($resolver->resolve('{{steps.action-2.output.sent}}', $context))->toBe('1');
    expect($resolver->resolve('{{steps.action-2.status}}', $context))->toBe('completed');
});

it('falls back to old flat format for backward compat', function () {
    $resolver = new VariableResolver();
    $context = [
        'record' => ['name' => 'Jane Doe'],
    ];

    expect($resolver->resolve('{{record.name}}', $context))->toBe('Jane Doe');
});

it('resolves built-in now variable', function () {
    $resolver = new VariableResolver();

    $result = $resolver->resolve('{{now}}', []);

    // Should be an ISO 8601 string
    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2}T/');
});

it('resolves built-in today variable', function () {
    $resolver = new VariableResolver();

    $result = $resolver->resolve('{{today}}', []);

    expect($result)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
});

it('resolves multiple variables in one template', function () {
    $company = new TestCompany(['name' => 'Acme']);

    $resolver = new VariableResolver();
    $context = [
        'trigger' => [
            'record' => $company,
            'event' => 'updated',
        ],
    ];

    $result = $resolver->resolve('{{trigger.record.name}} was {{trigger.event}}', $context);
    expect($result)->toBe('Acme was updated');
});

it('resolves array configs with model context', function () {
    $company = new TestCompany(['name' => 'Acme', 'domain' => 'acme.com']);

    $resolver = new VariableResolver();
    $context = [
        'trigger' => [
            'record' => $company,
        ],
    ];

    $config = [
        'to' => '{{trigger.record.domain}}',
        'subject' => 'Update for {{trigger.record.name}}',
    ];

    $resolved = $resolver->resolveArray($config, $context);

    expect($resolved['to'])->toBe('acme.com');
    expect($resolved['subject'])->toBe('Update for Acme');
});

it('returns empty string for invalid paths on model', function () {
    $company = new TestCompany(['name' => 'Acme']);

    $resolver = new VariableResolver();
    $context = [
        'trigger' => [
            'record' => $company,
        ],
    ];

    \Illuminate\Support\Facades\Log::shouldReceive('warning')->atLeast()->once();

    $result = $resolver->resolve('{{trigger.record.nonexistent_field}}', $context);
    expect($result)->toBe('');
});
