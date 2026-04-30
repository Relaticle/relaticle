<?php

declare(strict_types=1);

use Relaticle\Chat\Enums\AiModel;

mutates(AiModel::class);

it('returns alias model ids without dated suffixes', function (): void {
    expect(AiModel::ClaudeHaiku->modelId())->toBe('claude-haiku-4-5')
        ->and(AiModel::ClaudeSonnet->modelId())->toBe('claude-sonnet-4-5')
        ->and(AiModel::ClaudeOpus->modelId())->toBe('claude-opus-4-5');
});

it('returns null model id for Auto', function (): void {
    expect(AiModel::Auto->modelId())->toBeNull();
});

it('maps each anthropic model to anthropic provider', function (): void {
    expect(AiModel::ClaudeHaiku->provider())->toBe('anthropic')
        ->and(AiModel::ClaudeSonnet->provider())->toBe('anthropic')
        ->and(AiModel::ClaudeOpus->provider())->toBe('anthropic');
});
