<?php

declare(strict_types=1);

use Laravel\Ai\AnonymousAgent;
use Relaticle\Workflow\Actions\PromptCompletionAction;

it('calls AI agent and returns completion text', function () {
    AnonymousAgent::fake(['This is a great company summary.']);

    $action = new PromptCompletionAction();

    $config = [
        'prompt' => 'Summarize this: {{trigger.record.name}} is a great company',
        'model' => 'claude-haiku-4-5-20251001',
        'max_tokens' => 200,
        'temperature' => 0.7,
    ];

    $context = [
        'trigger' => ['record' => ['name' => 'Acme Corp']],
    ];

    $result = $action->execute($config, $context);

    expect($result)->toBeArray();
    expect($result['completion'])->toBe('This is a great company summary.');
    expect($result['model_used'])->toBeString();
    expect($result['tokens_used'])->toBeInt();
    expect($result['resolved_prompt'])->toContain('Acme Corp');
});

it('returns error when prompt is empty', function () {
    $action = new PromptCompletionAction();

    $result = $action->execute(['prompt' => ''], []);

    expect($result['error'])->toBe('Prompt is required');
    expect($result['completion'])->toBeNull();
});

it('resolves variable placeholders in prompt', function () {
    AnonymousAgent::fake(['Response with resolved vars']);

    $action = new PromptCompletionAction();

    $config = [
        'prompt' => 'Hello {{trigger.record.name}}, your status is {{trigger.record.status}}',
        'model' => 'claude-haiku-4-5-20251001',
    ];

    $context = [
        'trigger' => ['record' => ['name' => 'Alice', 'status' => 'active']],
    ];

    $result = $action->execute($config, $context);

    expect($result)->toBeArray();
    expect($result['resolved_prompt'])->toContain('Alice');
    expect($result['resolved_prompt'])->toContain('active');
    expect($result['resolved_prompt'])->not->toContain('{{');
});

it('returns error response when AI call fails', function () {
    AnonymousAgent::fake([fn () => throw new \RuntimeException('API rate limited')]);

    $action = new PromptCompletionAction();

    $config = [
        'prompt' => 'Test prompt',
        'model' => 'claude-haiku-4-5-20251001',
    ];

    $result = $action->execute($config, []);

    expect($result['error'])->toContain('Prompt completion failed');
    expect($result['completion'])->toBeNull();
    expect($result['resolved_prompt'])->toBe('Test prompt');
});

it('uses default provider and model when not specified', function () {
    AnonymousAgent::fake(['Default response']);

    $action = new PromptCompletionAction();

    $config = ['prompt' => 'Hello world'];

    $result = $action->execute($config, []);

    expect($result['completion'])->toBe('Default response');
    expect($result['model_used'])->toBeString();
});
