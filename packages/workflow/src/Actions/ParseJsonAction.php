<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Livewire\Component;

class ParseJsonAction extends BaseAction
{
    /**
     * Execute the parse JSON action, decoding a JSON string and extracting specified fields.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'json_path' (string), 'fields' (array)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $jsonPath = $config['json_path'] ?? '';
        $fields = $config['fields'] ?? [];

        if (empty($jsonPath)) {
            return ['error' => 'json_path is required', 'parsed' => null, 'raw' => null];
        }

        try {
            $jsonString = data_get($context, $jsonPath, '');

            if (is_array($jsonString) || is_object($jsonString)) {
                // Already decoded, use directly
                $decoded = is_object($jsonString) ? (array) $jsonString : $jsonString;
            } else {
                if (!is_string($jsonString) || empty($jsonString)) {
                    return [
                        'error' => 'No JSON string found at the specified path',
                        'parsed' => null,
                        'raw' => null,
                    ];
                }

                $decoded = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
            }

            // Extract specified fields, or return all if no fields specified
            if (!empty($fields)) {
                $parsed = [];
                foreach ($fields as $field) {
                    $parsed[$field] = data_get($decoded, $field);
                }
            } else {
                $parsed = $decoded;
            }

            return [
                'parsed' => $parsed,
                'raw' => $decoded,
            ];
        } catch (\JsonException $e) {
            return [
                'error' => 'JSON parsing failed: ' . $e->getMessage(),
                'parsed' => null,
                'raw' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Parse JSON failed: ' . $e->getMessage(),
                'parsed' => null,
                'raw' => null,
            ];
        }
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Parse JSON';
    }

    public static function category(): string
    {
        return 'Utilities';
    }

    public static function icon(): string
    {
        return 'heroicon-o-code-bracket';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'json_path' => ['type' => 'string', 'label' => 'JSON Path', 'required' => true],
            'fields' => ['type' => 'array', 'label' => 'Fields to Extract', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Select::make('json_path')
                ->label('JSON Source')
                ->required()
                ->searchable()
                ->placeholder('Select the field containing JSON...')
                ->helperText('Select the field containing the JSON string to parse')
                ->options(fn (?Component $livewire) => static::getFieldOptions($livewire, ['string', 'text'])),
            TagsInput::make('fields')
                ->label('Fields to Extract')
                ->placeholder('Add a dot-notation field path')
                ->helperText('Dot-notation paths to extract (e.g. data.name, items.0.id). Leave empty to return all.'),
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
            'parsed' => ['type' => 'object', 'label' => 'Extracted Fields'],
            'raw' => ['type' => 'object', 'label' => 'Raw Decoded JSON'],
        ];
    }
}
