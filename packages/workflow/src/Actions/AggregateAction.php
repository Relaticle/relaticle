<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class AggregateAction extends BaseAction
{
    /**
     * Execute the aggregate action, applying an aggregation operation to a collection of values.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'values_path' (string), 'operation' (string)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $valuesPath = $config['values_path'] ?? '';
        $operation = $config['operation'] ?? 'sum';

        if (empty($valuesPath)) {
            return ['error' => 'values_path is required', 'result' => null, 'count' => 0];
        }

        try {
            $values = data_get($context, $valuesPath, []);

            if (!is_array($values) && !($values instanceof \Traversable)) {
                return [
                    'error' => 'Resolved value is not an array or collection',
                    'result' => null,
                    'count' => 0,
                ];
            }

            $numericValues = collect($values)
                ->map(fn ($v) => is_numeric($v) ? (float) $v : null)
                ->filter(fn ($v) => $v !== null)
                ->values();

            $count = $numericValues->count();

            if ($count === 0 && $operation !== 'count') {
                return [
                    'result' => 0,
                    'count' => 0,
                    'operation' => $operation,
                ];
            }

            $result = match ($operation) {
                'sum' => $numericValues->sum(),
                'count' => count(collect($values)->toArray()),
                'average' => $count > 0 ? $numericValues->sum() / $count : 0,
                'min' => $numericValues->min(),
                'max' => $numericValues->max(),
                default => throw new \InvalidArgumentException("Unknown operation: {$operation}"),
            };

            return [
                'result' => $result,
                'count' => $operation === 'count' ? $result : $count,
                'operation' => $operation,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Aggregation failed: ' . $e->getMessage(),
                'result' => null,
                'count' => 0,
            ];
        }
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Aggregate values';
    }

    public static function category(): string
    {
        return 'Calculations';
    }

    public static function icon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'values_path' => ['type' => 'string', 'label' => 'Values Path', 'required' => true],
            'operation' => ['type' => 'select', 'label' => 'Operation', 'options' => ['sum', 'count', 'average', 'min', 'max'], 'required' => true],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            TextInput::make('values_path')
                ->label('Values Path')
                ->required()
                ->placeholder('trigger.record.line_items.*.amount')
                ->helperText('Dot-notation path to the array of values in the context'),
            Select::make('operation')
                ->label('Operation')
                ->options([
                    'sum' => 'Sum',
                    'count' => 'Count',
                    'average' => 'Average',
                    'min' => 'Minimum',
                    'max' => 'Maximum',
                ])
                ->required()
                ->default('sum'),
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
            'result' => ['type' => 'number', 'label' => 'Result'],
            'count' => ['type' => 'number', 'label' => 'Value Count'],
            'operation' => ['type' => 'string', 'label' => 'Operation Used'],
        ];
    }
}
