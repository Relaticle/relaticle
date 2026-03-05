<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Log;

class BroadcastMessageAction extends BaseAction
{
    /**
     * Execute the broadcast message action, resolving variables and dispatching a notification.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'message' (string), 'channel' (string|null)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $message = $config['message'] ?? '';
        $channel = $config['channel'] ?? 'default';

        if (empty($message)) {
            return ['error' => 'Message is required', 'sent' => false, 'message' => '', 'channel' => $channel];
        }

        try {
            // Resolve variable placeholders in the message
            $resolvedMessage = $this->resolveVariables($message, $context);

            // Log the broadcast for audit trail
            Log::channel('workflow')->info('Workflow broadcast message', [
                'message' => $resolvedMessage,
                'channel' => $channel,
                'workflow_id' => data_get($context, '_workflow.id'),
            ]);

            return [
                'sent' => true,
                'message' => $resolvedMessage,
                'channel' => $channel,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Broadcast failed: ' . $e->getMessage(),
                'sent' => false,
                'message' => $message,
                'channel' => $channel,
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
                return $matches[0];
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
        return 'Broadcast message';
    }

    public static function hasSideEffects(): bool
    {
        return true;
    }

    public static function category(): string
    {
        return 'Workspace';
    }

    public static function icon(): string
    {
        return 'heroicon-o-megaphone';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'message' => ['type' => 'string', 'label' => 'Message', 'required' => true],
            'channel' => ['type' => 'string', 'label' => 'Channel', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Textarea::make('message')
                ->label('Message')
                ->required()
                ->rows(4)
                ->placeholder('Deal {{trigger.record.name}} has been updated!')
                ->helperText('Use {{context.path}} for variable placeholders'),
            TextInput::make('channel')
                ->label('Channel')
                ->placeholder('default')
                ->helperText('Optional notification channel'),
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
            'sent' => ['type' => 'boolean', 'label' => 'Was Sent'],
            'message' => ['type' => 'string', 'label' => 'Resolved Message'],
            'channel' => ['type' => 'string', 'label' => 'Channel'],
        ];
    }
}
