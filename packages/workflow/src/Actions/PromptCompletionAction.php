<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use function Laravel\Ai\agent;

class PromptCompletionAction extends BaseAction
{
    /**
     * Execute the prompt completion action using Laravel AI SDK.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'prompt', 'provider', 'model', 'max_tokens', 'temperature'
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $prompt = $config['prompt'] ?? '';
        $model = $config['model'] ?? 'claude-haiku-4-5-20251001';
        $maxTokens = (int) ($config['max_tokens'] ?? 500);
        $temperature = (float) ($config['temperature'] ?? 0.7);
        $provider = $config['provider'] ?? 'anthropic';

        if (empty($prompt)) {
            return [
                'error' => 'Prompt is required',
                'completion' => null,
                'model_used' => $model,
                'tokens_used' => 0,
            ];
        }

        try {
            $resolvedPrompt = $this->resolveVariables($prompt, $context);

            $response = agent(
                instructions: 'You are a helpful AI assistant for a CRM workflow automation system. Follow the user prompt precisely and provide clear, concise responses.',
            )->prompt(
                prompt: $resolvedPrompt,
                provider: $provider,
                model: $model,
                timeout: 60,
            );

            return [
                'completion' => $response->text,
                'model_used' => $response->meta->model ?? $model,
                'tokens_used' => ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0),
                'resolved_prompt' => $resolvedPrompt,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Prompt completion failed: ' . $e->getMessage(),
                'completion' => null,
                'model_used' => $model,
                'tokens_used' => 0,
                'resolved_prompt' => $resolvedPrompt ?? $prompt,
            ];
        }
    }

    /**
     * Resolve {{variable.path}} placeholders in a string using the workflow context.
     */
    private function resolveVariables(string $template, array $context): string
    {
        return (string) preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);
            $value = data_get($context, $path);

            if ($value === null) {
                return $matches[0]; // Leave placeholder if not found
            }

            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            return (string) $value;
        }, $template);
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Prompt completion';
    }

    public static function category(): string
    {
        return 'AI';
    }

    public static function icon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'prompt' => ['type' => 'string', 'label' => 'Prompt', 'required' => true],
            'provider' => ['type' => 'string', 'label' => 'AI Provider', 'required' => false],
            'model' => ['type' => 'string', 'label' => 'AI Model', 'required' => false],
            'max_tokens' => ['type' => 'integer', 'label' => 'Max Tokens', 'required' => false],
            'temperature' => ['type' => 'number', 'label' => 'Temperature', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Textarea::make('prompt')
                ->label('Prompt')
                ->required()
                ->rows(5)
                ->placeholder('Summarize the following record: {{trigger.record.name}} - {{trigger.record.description}}')
                ->helperText('Use {{context.path}} for variable placeholders'),
            Select::make('provider')
                ->label('AI Provider')
                ->options([
                    'anthropic' => 'Anthropic (Claude)',
                    'openai' => 'OpenAI (GPT)',
                    'gemini' => 'Google (Gemini)',
                ])
                ->default('anthropic'),
            Select::make('model')
                ->label('AI Model')
                ->options([
                    'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fast)',
                    'claude-sonnet-4-5-20250514' => 'Claude Sonnet 4.5 (Balanced)',
                    'gpt-4o-mini' => 'GPT-4o Mini (Fast)',
                    'gpt-4o' => 'GPT-4o (Balanced)',
                ])
                ->default('claude-haiku-4-5-20251001'),
            TextInput::make('max_tokens')
                ->label('Max Tokens')
                ->numeric()
                ->default(500)
                ->minValue(1)
                ->maxValue(4000),
            TextInput::make('temperature')
                ->label('Temperature')
                ->numeric()
                ->default(0.7)
                ->minValue(0)
                ->maxValue(1)
                ->step(0.1)
                ->helperText('0 = deterministic, 1 = creative'),
        ];
    }

    /**
     * Get the output schema describing what variables this action produces.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function outputSchema(): array
    {
        return [
            'completion' => ['type' => 'string', 'label' => 'AI Completion'],
            'model_used' => ['type' => 'string', 'label' => 'Model Used'],
            'tokens_used' => ['type' => 'number', 'label' => 'Tokens Used'],
        ];
    }
}
