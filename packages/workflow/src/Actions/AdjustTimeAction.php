<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Relaticle\Workflow\Forms\Actions\VariablePickerAction;

class AdjustTimeAction extends BaseAction
{
    /**
     * Execute the adjust time action, adding or subtracting time from a date.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'date_path' (string), 'amount' (int), 'unit' (string), 'direction' (string)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $datePath = $config['date_path'] ?? '';
        $amount = (int) ($config['amount'] ?? 0);
        $unit = $config['unit'] ?? 'days';
        $direction = $config['direction'] ?? 'add';

        if (empty($datePath)) {
            return ['error' => 'date_path is required', 'original' => null, 'adjusted' => null, 'iso' => null];
        }

        try {
            $dateValue = data_get($context, $datePath);

            if (empty($dateValue)) {
                return [
                    'error' => 'No date found at the specified path',
                    'original' => null,
                    'adjusted' => null,
                    'iso' => null,
                ];
            }

            $date = $dateValue instanceof Carbon ? $dateValue->copy() : Carbon::parse($dateValue);
            $original = $date->toIso8601String();

            $adjusted = match ($direction) {
                'add' => $this->addTime($date, $amount, $unit),
                'subtract' => $this->subtractTime($date, $amount, $unit),
                default => throw new \InvalidArgumentException("Invalid direction: {$direction}"),
            };

            return [
                'original' => $original,
                'adjusted' => $adjusted->toDateTimeString(),
                'iso' => $adjusted->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Time adjustment failed: ' . $e->getMessage(),
                'original' => null,
                'adjusted' => null,
                'iso' => null,
            ];
        }
    }

    private function addTime(Carbon $date, int $amount, string $unit): Carbon
    {
        return match ($unit) {
            'minutes' => $date->addMinutes($amount),
            'hours' => $date->addHours($amount),
            'days' => $date->addDays($amount),
            'weeks' => $date->addWeeks($amount),
            'months' => $date->addMonths($amount),
            default => throw new \InvalidArgumentException("Unknown unit: {$unit}"),
        };
    }

    private function subtractTime(Carbon $date, int $amount, string $unit): Carbon
    {
        return match ($unit) {
            'minutes' => $date->subMinutes($amount),
            'hours' => $date->subHours($amount),
            'days' => $date->subDays($amount),
            'weeks' => $date->subWeeks($amount),
            'months' => $date->subMonths($amount),
            default => throw new \InvalidArgumentException("Unknown unit: {$unit}"),
        };
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Adjust time';
    }

    public static function category(): string
    {
        return 'Calculations';
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
            'date_path' => ['type' => 'string', 'label' => 'Date Path', 'required' => true],
            'amount' => ['type' => 'integer', 'label' => 'Amount', 'required' => true],
            'unit' => ['type' => 'select', 'label' => 'Unit', 'options' => ['minutes', 'hours', 'days', 'weeks', 'months'], 'required' => true],
            'direction' => ['type' => 'select', 'label' => 'Direction', 'options' => ['add', 'subtract'], 'required' => true],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            TextInput::make('date_path')
                ->label('Date Path')
                ->required()
                ->placeholder('trigger.record.created_at')
                ->helperText('Dot-notation path to the date value in the context')
                ->suffixAction(VariablePickerAction::make('pickDatePath')->forField('date_path')),
            TextInput::make('amount')
                ->label('Amount')
                ->numeric()
                ->required()
                ->minValue(0)
                ->default(1),
            Select::make('unit')
                ->label('Unit')
                ->options([
                    'minutes' => 'Minutes',
                    'hours' => 'Hours',
                    'days' => 'Days',
                    'weeks' => 'Weeks',
                    'months' => 'Months',
                ])
                ->required()
                ->default('days'),
            Select::make('direction')
                ->label('Direction')
                ->options([
                    'add' => 'Add (future)',
                    'subtract' => 'Subtract (past)',
                ])
                ->required()
                ->default('add'),
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
            'original' => ['type' => 'string', 'label' => 'Original Date'],
            'adjusted' => ['type' => 'string', 'label' => 'Adjusted Date'],
            'iso' => ['type' => 'string', 'label' => 'ISO 8601 Date'],
        ];
    }
}
