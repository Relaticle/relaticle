<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Log;
use Relaticle\Workflow\Forms\Actions\VariablePickerAction;

class LogMessageAction extends BaseAction
{
    public function execute(array $config, array $context): array
    {
        $message = $config['message'] ?? 'No message configured';

        // Resolve any variables in the message
        $resolvedMessage = $this->resolveVariables($message, $context);

        Log::channel('stack')->info('[Workflow] ' . $resolvedMessage, [
            'workflow_id' => $context['_workflow']->id ?? null,
        ]);

        return [
            'message' => $resolvedMessage,
            'logged' => true,
        ];
    }

    /**
     * Simple variable replacement for {{variable.path}} patterns.
     */
    private function resolveVariables(string $message, array $context): string
    {
        return preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);
            $value = data_get($context, $path);

            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            return (string) ($value ?? $matches[0]);
        }, $message);
    }

    public static function label(): string
    {
        return 'Log Message';
    }

    public static function hasSideEffects(): bool
    {
        return false;
    }

    public static function category(): string
    {
        return 'Utilities';
    }

    public static function icon(): string
    {
        return 'heroicon-o-chat-bubble-left';
    }

    public static function configSchema(): array
    {
        return [
            'message' => ['type' => 'string', 'label' => 'Message', 'required' => true],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Textarea::make('message')
                ->label('Message')
                ->placeholder('Enter message to log (supports {{variables}})')
                ->required()
                ->rows(3)
                ->suffixAction(
                    VariablePickerAction::make('pickLogMessage')
                        ->forField('message')
                ),
        ];
    }

    public static function outputSchema(): array
    {
        return [
            'message' => ['type' => 'string', 'label' => 'Logged Message'],
            'logged' => ['type' => 'boolean', 'label' => 'Was Logged'],
        ];
    }
}
