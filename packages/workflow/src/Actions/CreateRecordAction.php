<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Relaticle\Workflow\Forms\Actions\VariablePickerAction;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\WorkflowManager;

class CreateRecordAction extends BaseAction
{
    public function execute(array $config, array $context): array
    {
        $entityType = $config['entity_type'] ?? null;

        if (!$entityType) {
            return ['error' => 'entity_type is required', 'created' => false];
        }

        $schema = app(RelaticleSchema::class);
        $entity = $schema->getEntity($entityType);

        if (!$entity) {
            return ['error' => "Unknown entity type: {$entityType}", 'created' => false];
        }

        $modelClass = $entity->modelClass;
        $rawMappings = $config['field_mappings'] ?? [];
        $customFieldMappings = $config['custom_field_mappings'] ?? [];

        // Support both old KeyValue format and new Repeater format
        $fieldMappings = [];
        if (!empty($rawMappings) && isset($rawMappings[0]) && is_array($rawMappings[0]) && array_key_exists('field', $rawMappings[0])) {
            // New Repeater format: [{field: "name", value: "..."}, ...]
            foreach ($rawMappings as $mapping) {
                $fieldKey = $mapping['field'] ?? '';
                $value = $mapping['value'] ?? '';

                if (str_starts_with($fieldKey, 'custom.')) {
                    $customFieldMappings[substr($fieldKey, 7)] = $value;
                } else {
                    $fieldMappings[$fieldKey] = $value;
                }
            }
        } else {
            // Legacy KeyValue format: {field: value, ...}
            $fieldMappings = $rawMappings;
        }

        // Set tenant and creator from workflow context
        $workflow = $context['_workflow'] ?? null;
        $attributes = $fieldMappings;

        if ($workflow) {
            $tenancyConfig = app(WorkflowManager::class)->getTenancyConfig();
            $scopeColumn = $tenancyConfig['scopeColumn'] ?? 'tenant_id';
            // Entity tables may use a different column (e.g. team_id) than workflows (tenant_id).
            // Try the configured scope column first; if the entity table doesn't have it, fall back to team_id.
            $entityScopeColumn = $this->resolveEntityScopeColumn($modelClass, $scopeColumn);
            $attributes[$entityScopeColumn] = $workflow->tenant_id ?? ($attributes[$entityScopeColumn] ?? null);
            $attributes['creator_id'] = $workflow->creator_id ?? ($attributes['creator_id'] ?? null);
        }

        // Set creation source to SYSTEM for workflow-created records
        if (class_exists(\App\Enums\CreationSource::class)) {
            $attributes['creation_source'] = \App\Enums\CreationSource::SYSTEM;
        }

        try {
            $record = $modelClass::create($attributes);
        } catch (\Throwable $e) {
            return ['error' => 'Failed to create record: ' . class_basename($e) . ' - ' . $e->getMessage(), 'created' => false];
        }

        // Save custom field values
        if (!empty($customFieldMappings) && method_exists($record, 'saveCustomFields')) {
            $record->saveCustomFields($customFieldMappings);
        }

        return [
            'id' => $record->id,
            'created' => true,
            'record' => $record->toArray(),
        ];
    }

    public static function label(): string
    {
        return 'Create Record';
    }

    public static function hasSideEffects(): bool
    {
        return true;
    }

    public static function category(): string
    {
        return 'Records';
    }

    public static function icon(): string
    {
        return 'heroicon-o-document-plus';
    }

    public static function configSchema(): array
    {
        return [
            'entity_type' => ['type' => 'string', 'label' => 'Entity Type', 'required' => true],
            'field_mappings' => ['type' => 'object', 'label' => 'Field Mappings', 'required' => true],
            'custom_field_mappings' => ['type' => 'object', 'label' => 'Custom Field Mappings', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Select::make('entity_type')
                ->label('Entity Type')
                ->options(fn () => self::getEntityOptions())
                ->required()
                ->live(),
            Repeater::make('field_mappings')
                ->label('Field Values')
                ->schema([
                    Select::make('field')
                        ->label('Field')
                        ->searchable()
                        ->options(function (callable $get) {
                            $entityType = $get('../../entity_type');
                            if (!$entityType) {
                                return [];
                            }
                            return self::getFieldOptionsForEntity($entityType);
                        })
                        ->placeholder('Select field...')
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('value')
                        ->label('Value')
                        ->placeholder('Value or {{variable}}')
                        ->columnSpan(1)
                        ->suffixAction(
                            VariablePickerAction::make('pickCreateValue')
                                ->forField('value')
                        ),
                ])
                ->columns(2)
                ->addActionLabel('Add field mapping')
                ->defaultItems(0),
        ];
    }

    protected static function getEntityOptions(): array
    {
        try {
            return collect(app(RelaticleSchema::class)->getEntities())
                ->mapWithKeys(fn ($entity) => [$entity->key => $entity->label])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    protected static function getFieldOptionsForEntity(string $entityType): array
    {
        try {
            $schema = app(RelaticleSchema::class);
            $fields = $schema->getFields($entityType);

            $options = [];
            foreach ($fields as $field) {
                $key = $field->isCustomField ? "custom.{$field->key}" : $field->key;
                $group = $field->isCustomField ? 'Custom' : 'Standard';
                $options[$key] = "[{$group}] {$field->label}";
            }

            return $options;
        } catch (\Throwable) {
            return [];
        }
    }

    public static function outputSchema(): array
    {
        return [
            'id' => ['type' => 'string', 'label' => 'Record ID'],
            'created' => ['type' => 'boolean', 'label' => 'Was Created'],
        ];
    }
}
