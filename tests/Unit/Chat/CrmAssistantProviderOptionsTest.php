<?php

declare(strict_types=1);

use Laravel\Ai\Enums\Lab;
use Relaticle\Chat\Agents\CrmAssistant;

it('disables parallel tool calls on Anthropic', function (): void {
    $agent = app(CrmAssistant::class);

    $opts = $agent->providerOptions(Lab::Anthropic);

    expect($opts)->toMatchArray([
        'tool_choice' => [
            'type' => 'auto',
            'disable_parallel_tool_use' => true,
        ],
    ]);
});

it('disables parallel tool calls on OpenAI', function (): void {
    $agent = app(CrmAssistant::class);

    $opts = $agent->providerOptions(Lab::OpenAI);

    expect($opts)->toHaveKey('parallel_tool_calls', false);
});

it('returns an empty options array on Gemini (no equivalent flag)', function (): void {
    $agent = app(CrmAssistant::class);

    $opts = $agent->providerOptions(Lab::Gemini);

    expect($opts)->toBe([]);
});
