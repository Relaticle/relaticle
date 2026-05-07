<?php

declare(strict_types=1);

use Relaticle\Chat\Enums\AiModel;

mutates(AiModel::class);

it('returns the latest Anthropic model ids', function (): void {
    expect(AiModel::ClaudeSonnet->modelId())->toBe('claude-sonnet-4-6')
        ->and(AiModel::ClaudeOpus->modelId())->toBe('claude-opus-4-7');
});

it('returns null model id for Auto', function (): void {
    expect(AiModel::Auto->modelId())->toBeNull();
});

it('maps each anthropic model to anthropic provider', function (): void {
    expect(AiModel::ClaudeSonnet->provider())->toBe('anthropic')
        ->and(AiModel::ClaudeOpus->provider())->toBe('anthropic');
});

it('maps each OpenAI model to openai provider', function (): void {
    expect(AiModel::Gpt5_5->provider())->toBe('openai')
        ->and(AiModel::Gpt5_4->provider())->toBe('openai');
});

it('maps each Gemini model to gemini provider', function (): void {
    expect(AiModel::Gemini3Flash->provider())->toBe('gemini')
        ->and(AiModel::Gemini31Pro->provider())->toBe('gemini');
});
