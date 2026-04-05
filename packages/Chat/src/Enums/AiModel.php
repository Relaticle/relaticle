<?php

declare(strict_types=1);

namespace Relaticle\Chat\Enums;

enum AiModel: string
{
    case Auto = 'auto';
    case ClaudeSonnet = 'claude-sonnet';
    case ClaudeOpus = 'claude-opus';
    case Gpt4o = 'gpt-4o';
    case GeminiPro = 'gemini-pro';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Auto',
            self::ClaudeSonnet => 'Claude Sonnet',
            self::ClaudeOpus => 'Claude Opus',
            self::Gpt4o => 'GPT-4o',
            self::GeminiPro => 'Gemini Pro',
        };
    }

    public function provider(): ?string
    {
        return match ($this) {
            self::Auto => null,
            self::ClaudeSonnet, self::ClaudeOpus => 'anthropic',
            self::Gpt4o => 'openai',
            self::GeminiPro => 'gemini',
        };
    }

    public function modelId(): ?string
    {
        return match ($this) {
            self::Auto => null,
            self::ClaudeSonnet => 'claude-sonnet-4-5-20250514',
            self::ClaudeOpus => 'claude-opus-4-20250514',
            self::Gpt4o => 'gpt-4o',
            self::GeminiPro => 'gemini-2.5-pro',
        };
    }

    public function creditMultiplier(): float
    {
        return match ($this) {
            self::Auto, self::ClaudeSonnet => 1.0,
            self::ClaudeOpus => 3.0,
            self::Gpt4o => 1.5,
            self::GeminiPro => 1.0,
        };
    }
}
