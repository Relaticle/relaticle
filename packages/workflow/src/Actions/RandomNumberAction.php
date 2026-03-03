<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\TextInput;

class RandomNumberAction extends BaseAction
{
    /**
     * Execute the random number action, generating a random integer within the configured range.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'min' (int), 'max' (int)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $min = (int) ($config['min'] ?? 1);
        $max = (int) ($config['max'] ?? 100);

        if ($min > $max) {
            return [
                'error' => 'Minimum value cannot be greater than maximum value',
                'result' => null,
            ];
        }

        try {
            $result = random_int($min, $max);

            return [
                'result' => $result,
                'min' => $min,
                'max' => $max,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Random number generation failed: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Random number';
    }

    public static function category(): string
    {
        return 'Calculations';
    }

    public static function icon(): string
    {
        return 'heroicon-o-sparkles';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'min' => ['type' => 'integer', 'label' => 'Minimum', 'required' => false],
            'max' => ['type' => 'integer', 'label' => 'Maximum', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            TextInput::make('min')
                ->label('Minimum')
                ->numeric()
                ->default(1)
                ->placeholder('1'),
            TextInput::make('max')
                ->label('Maximum')
                ->numeric()
                ->default(100)
                ->placeholder('100'),
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
            'result' => ['type' => 'number', 'label' => 'Random Number'],
            'min' => ['type' => 'number', 'label' => 'Minimum Used'],
            'max' => ['type' => 'number', 'label' => 'Maximum Used'],
        ];
    }
}
