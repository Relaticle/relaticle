<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Workflow\Forms\Actions\VariablePickerAction;
use Relaticle\Workflow\Schema\RelaticleSchema;

class UpdateRecordAction extends BaseAction
{
    public function execute(array $config, array $context): array
    {
        $record = $this->resolveRecord($config, $context);

        if (!$record) {
            return ['error' => 'Could not resolve record to update', 'updated' => false];
        }

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

        $record->update($fieldMappings);

        if (!empty($customFieldMappings) && method_exists($record, 'saveCustomFields')) {
            $record->saveCustomFields($customFieldMappings);
        }

        return [
            'id' => $record->id,
            'updated' => true,
        ];
    }

    private function resolveRecord(array $config, array $context): ?Model
    {
        $source = $config['record_source'] ?? 'trigger';
        $tenantId = $context['_workflow']->tenant_id ?? ($context['_workflow']['tenant_id'] ?? null);

        if ($source === 'trigger') {
            $record = $context['trigger']['record'] ?? null;

            // Re-hydrate from model_class + model_id if record is an array
            if (is_array($record) && isset($context['trigger']['model_class'], $context['trigger']['model_id'])) {
                $modelClass = $context['trigger']['model_class'];
                $query = $modelClass::query();
                if ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }
                return $query->find($context['trigger']['model_id']);
            }

            return $record instanceof Model ? $record : null;
        }

        if ($source === 'step') {
            $stepNodeId = $config['step_node_id'] ?? null;
            if (!$stepNodeId) {
                return null;
            }

            $stepOutput = $context['steps'][$stepNodeId]['output'] ?? null;
            if (!$stepOutput || empty($stepOutput['id'])) {
                return null;
            }

            // Try to find the record by ID using the entity type from the step
            $entityType = $stepOutput['_entity_type'] ?? ($config['entity_type'] ?? null);
            if ($entityType) {
                $schema = app(\Relaticle\Workflow\Schema\RelaticleSchema::class);
                $entity = $schema->getEntity($entityType);
                if ($entity) {
                    $query = $entity->modelClass::query();
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }
                    return $query->find($stepOutput['id']);
                }
            }

            // If step stored the record directly
            if (isset($stepOutput['_record']) && $stepOutput['_record'] instanceof Model) {
                return $stepOutput['_record'];
            }

            return null;
        }

        return null;
    }

    public static function label(): string
    {
        return 'Update Record';
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
        return 'heroicon-o-pencil-square';
    }

    public static function configSchema(): array
    {
        return [
            'record_source' => ['type' => 'string', 'label' => 'Record Source', 'required' => true],
            'step_node_id' => ['type' => 'string', 'label' => 'Step Node ID', 'required' => false],
            'entity_type' => ['type' => 'string', 'label' => 'Entity Type', 'required' => false],
            'field_mappings' => ['type' => 'object', 'label' => 'Field Mappings', 'required' => true],
            'custom_field_mappings' => ['type' => 'object', 'label' => 'Custom Field Mappings', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Select::make('record_source')
                ->label('Record Source')
                ->options([
                    'trigger' => 'Trigger Record',
                    'step' => 'From Previous Step',
                ])
                ->required()
                ->live(),
            Select::make('step_node_id')
                ->label('Source Step')
                ->searchable()
                ->options(fn () => [])
                ->placeholder('Select upstream step...')
                ->visible(fn ($get) => $get('record_source') === 'step')
                ->helperText('Select the step that found or created the record'),
            Select::make('entity_type')
                ->label('Entity Type')
                ->options(fn () => self::getEntityOptions())
                ->visible(fn ($get) => $get('record_source') === 'step')
                ->helperText('Select entity type when updating a record from a previous step')
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
                            VariablePickerAction::make('pickUpdateValue')
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
            'updated' => ['type' => 'boolean', 'label' => 'Was Updated'],
        ];
    }
}
