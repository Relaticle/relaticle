<?php

declare(strict_types=1);

use Laravel\Ai\StructuredAnonymousAgent;
use Relaticle\Workflow\Actions\ClassifyAction;

it('classifies input text into a category using AI', function () {
    StructuredAnonymousAgent::fake([
        ['category' => 'Bug Report', 'confidence' => 0.92],
    ]);

    $action = new ClassifyAction();

    $config = [
        'input_path' => 'trigger.record.description',
        'categories' => ['Bug Report', 'Feature Request', 'Question'],
    ];

    $context = [
        'trigger' => [
            'record' => ['description' => 'The login button is broken and throws a 500 error'],
        ],
    ];

    $result = $action->execute($config, $context);

    expect($result)->toBeArray();
    expect($result['category'])->toBe('Bug Report');
    expect($result['confidence'])->toBe(0.92);
    expect($result['input_text'])->toContain('login button');
});

it('returns error when input_path is empty', function () {
    $action = new ClassifyAction();

    $result = $action->execute(['input_path' => '', 'categories' => ['A']], []);

    expect($result['error'])->toBe('input_path is required');
});

it('returns error when categories are empty', function () {
    $action = new ClassifyAction();

    $result = $action->execute(['input_path' => 'some.path', 'categories' => []], []);

    expect($result['error'])->toBe('At least one category is required');
});

it('falls back to keyword matching on AI failure', function () {
    StructuredAnonymousAgent::fake([fn () => throw new \RuntimeException('API rate limited')]);

    $action = new ClassifyAction();

    $config = [
        'input_path' => 'trigger.record.text',
        'categories' => ['Sales', 'Support', 'Billing'],
    ];

    $context = [
        'trigger' => [
            'record' => ['text' => 'I need help with my billing invoice'],
        ],
    ];

    $result = $action->execute($config, $context);

    expect($result)->toBeArray();
    expect($result['category'])->toBeIn(['Sales', 'Support', 'Billing']);
    expect($result['model'])->toBe('keyword_fallback');
    expect($result['ai_error'])->toContain('API rate limited');
});

it('returns first category with zero confidence for empty text', function () {
    $action = new ClassifyAction();

    $config = [
        'input_path' => 'trigger.record.text',
        'categories' => ['Bug', 'Feature'],
    ];

    $context = [
        'trigger' => ['record' => ['text' => '']],
    ];

    $result = $action->execute($config, $context);

    expect($result['category'])->toBe('Bug');
    expect($result['confidence'])->toBe(0.0);
});

it('uses default provider and model when not specified', function () {
    StructuredAnonymousAgent::fake([
        ['category' => 'Support', 'confidence' => 0.85],
    ]);

    $action = new ClassifyAction();

    $config = [
        'input_path' => 'trigger.record.text',
        'categories' => ['Sales', 'Support'],
    ];

    $context = [
        'trigger' => ['record' => ['text' => 'I need help with setup']],
    ];

    $result = $action->execute($config, $context);

    expect($result['category'])->toBe('Support');
    expect($result['confidence'])->toBe(0.85);
});
