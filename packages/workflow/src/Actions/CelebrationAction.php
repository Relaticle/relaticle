<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Log;

class CelebrationAction extends BaseAction
{
    /**
     * Execute the celebration action, creating a celebration event.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'message' (string), 'type' (string)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $message = $config['message'] ?? '';
        $type = $config['type'] ?? 'confetti';

        if (empty($message)) {
            return ['error' => 'Message is required', 'sent' => false, 'message' => '', 'type' => $type];
        }

        try {
            // Resolve variable placeholders in the message
            $resolvedMessage = $this->resolveVariables($message, $context);

            // Log the celebration event
            Log::channel('workflow')->info('Workflow celebration event', [
                'message' => $resolvedMessage,
                'type' => $type,
                'workflow_id' => data_get($context, '_workflow.id'),
            ]);

            return [
                'sent' => true,
                'message' => $resolvedMessage,
                'type' => $type,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Celebration event failed: ' . $e->getMessage(),
                'sent' => false,
                'message' => $message,
                'type' => $type,
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
        return 'Celebration';
    }

    public static function category(): string
    {
        return 'Workspace';
    }

    public static function icon(): string
    {
        return 'heroicon-o-trophy';
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
            'type' => ['type' => 'select', 'label' => 'Celebration Type', 'options' => ['confetti', 'fireworks', 'milestone'], 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Textarea::make('message')
                ->label('Message')
                ->required()
                ->rows(3)
                ->placeholder('Congratulations! A new milestone has been reached!'),
            Select::make('type')
                ->label('Celebration Type')
                ->options([
                    'confetti' => 'Confetti',
                    'fireworks' => 'Fireworks',
                    'milestone' => 'Milestone',
                ])
                ->default('confetti'),
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
            'message' => ['type' => 'string', 'label' => 'Message'],
            'type' => ['type' => 'string', 'label' => 'Celebration Type'],
        ];
    }
}
