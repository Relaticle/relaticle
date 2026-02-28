<?php

declare(strict_types=1);

use Relaticle\Workflow\Engine\VariableResolver;

it('resolves {{record.field_name}} from context', function () {
    $resolver = new VariableResolver();
    $context = ['record' => ['name' => 'Acme Corp', 'email' => 'hello@acme.com']];

    expect($resolver->resolve('Hello {{record.name}}', $context))->toBe('Hello Acme Corp');
    expect($resolver->resolve('{{record.email}}', $context))->toBe('hello@acme.com');
});

it('resolves nested variables {{record.company.name}}', function () {
    $resolver = new VariableResolver();
    $context = ['record' => ['company' => ['name' => 'Acme']]];

    expect($resolver->resolve('{{record.company.name}}', $context))->toBe('Acme');
});

it('resolves {{now}} and {{today}} date variables', function () {
    $resolver = new VariableResolver();

    $result = $resolver->resolve('Date: {{today}}', []);

    expect($result)->toContain('Date: ' . now()->toDateString());
});

it('resolves {{trigger.user.name}}', function () {
    $resolver = new VariableResolver();
    $context = ['trigger' => ['user' => ['name' => 'John']]];

    expect($resolver->resolve('By {{trigger.user.name}}', $context))->toBe('By John');
});

it('returns empty string for missing variables', function () {
    $resolver = new VariableResolver();

    expect($resolver->resolve('Hello {{record.missing}}', []))->toBe('Hello ');
});

it('resolves variables in arrays recursively', function () {
    $resolver = new VariableResolver();
    $context = ['record' => ['name' => 'Acme']];

    $config = [
        'subject' => 'Welcome {{record.name}}',
        'body' => 'Hi {{record.name}}, welcome!',
        'nested' => ['value' => '{{record.name}}'],
    ];

    $resolved = $resolver->resolveArray($config, $context);

    expect($resolved['subject'])->toBe('Welcome Acme');
    expect($resolved['body'])->toBe('Hi Acme, welcome!');
    expect($resolved['nested']['value'])->toBe('Acme');
});

it('leaves non-variable strings untouched', function () {
    $resolver = new VariableResolver();

    expect($resolver->resolve('No variables here', []))->toBe('No variables here');
});
