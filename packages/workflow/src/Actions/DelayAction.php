<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class DelayAction extends BaseAction
{
    /**
     * Execute the delay action, returning delay configuration details.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'duration' (int), 'unit' (string: minutes|hours|days)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $duration = $config['duration'] ?? 0;
        $unit = $config['unit'] ?? 'minutes';

        return [
            'delayed' => true,
            'duration' => $duration,
            'unit' => $unit,
            'delay_seconds' => $this->toSeconds($duration, $unit),
        ];
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Delay / Wait';
    }

    public static function category(): string
    {
        return 'Flow Control';
    }

    public static function icon(): string
    {
        return 'heroicon-o-clock';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'duration' => ['type' => 'integer', 'label' => 'Duration', 'required' => true],
            'unit' => ['type' => 'select', 'label' => 'Unit', 'options' => ['minutes', 'hours', 'days']],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            TextInput::make('duration')
                ->label('Duration')
                ->numeric()
                ->required()
                ->minValue(0),
            Select::make('unit')
                ->label('Unit')
                ->options([
                    'minutes' => 'Minutes',
                    'hours' => 'Hours',
                    'days' => 'Days',
                ])
                ->default('minutes'),
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
            'delayed' => ['type' => 'boolean', 'label' => 'Was Delayed'],
            'delay_seconds' => ['type' => 'number', 'label' => 'Delay Seconds'],
        ];
    }

    /**
     * Convert a duration in the given unit to seconds.
     */
    private function toSeconds(int $duration, string $unit): int
    {
        return match ($unit) {
            'minutes' => $duration * 60,
            'hours' => $duration * 3600,
            'days' => $duration * 86400,
            default => $duration * 60,
        };
    }
}
