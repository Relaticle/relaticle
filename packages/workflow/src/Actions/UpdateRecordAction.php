<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

class UpdateRecordAction extends BaseAction
{
    public function execute(array $config, array $context): array
    {
        $record = $this->resolveRecord($config, $context);

        if (!$record) {
            return ['error' => 'Could not resolve record to update', 'updated' => false];
        }

        $fieldMappings = $config['field_mappings'] ?? [];
        $customFieldMappings = $config['custom_field_mappings'] ?? [];

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

        if ($source === 'trigger') {
            return $context['trigger']['record'] ?? null;
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
                    return $entity->modelClass::find($stepOutput['id']);
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
            TextInput::make('step_node_id')
                ->label('Step Node ID')
                ->placeholder('e.g. action-2')
                ->visible(fn ($get) => $get('record_source') === 'step'),
            KeyValue::make('field_mappings')
                ->label('Field Values')
                ->keyLabel('Field')
                ->valueLabel('Value')
                ->addActionLabel('Add field'),
            KeyValue::make('custom_field_mappings')
                ->label('Custom Field Values')
                ->keyLabel('Custom Field')
                ->valueLabel('Value')
                ->addActionLabel('Add custom field'),
        ];
    }

    public static function outputSchema(): array
    {
        return [
            'id' => ['type' => 'string', 'label' => 'Record ID'],
            'updated' => ['type' => 'boolean', 'label' => 'Was Updated'],
        ];
    }
}
