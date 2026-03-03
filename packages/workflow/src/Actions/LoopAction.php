<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Livewire\Component;

class LoopAction extends BaseAction
{
    /**
     * Execute the loop action, returning metadata about the collection.
     *
     * The actual iteration is handled by the WorkflowExecutor, which calls
     * this action to record the loop step and then iterates over the collection
     * to execute downstream nodes for each item.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'collection' (string dot-notation path)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $collectionPath = $config['collection'] ?? '';
        $items = data_get($context, $collectionPath, []);

        return [
            'collection_path' => $collectionPath,
            'item_count' => is_array($items) || $items instanceof \Countable ? count($items) : 0,
        ];
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Loop / For Each';
    }

    public static function category(): string
    {
        return 'Flow Control';
    }

    public static function icon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'collection' => ['type' => 'string', 'label' => 'Collection Path', 'required' => true],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Select::make('collection')
                ->label('Collection to Loop Over')
                ->required()
                ->searchable()
                ->placeholder('Select a field...')
                ->helperText('Select the array field to iterate over')
                ->options(fn (?Component $livewire) => static::getFieldOptions($livewire, null)),
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
            'item_count' => ['type' => 'number', 'label' => 'Item Count'],
        ];
    }
}
