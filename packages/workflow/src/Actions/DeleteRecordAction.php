<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

class DeleteRecordAction extends BaseAction
{
    public function execute(array $config, array $context): array
    {
        $record = $this->resolveRecord($config, $context);

        if (!$record) {
            return ['error' => 'Could not resolve record to delete', 'deleted' => false];
        }

        $recordId = $record->id;
        $record->delete();

        return [
            'id' => $recordId,
            'deleted' => true,
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

            $entityType = $stepOutput['_entity_type'] ?? ($config['entity_type'] ?? null);
            if ($entityType) {
                $schema = app(\Relaticle\Workflow\Schema\RelaticleSchema::class);
                $entity = $schema->getEntity($entityType);
                if ($entity) {
                    return $entity->modelClass::find($stepOutput['id']);
                }
            }

            return null;
        }

        return null;
    }

    public static function label(): string
    {
        return 'Delete Record';
    }

    public static function category(): string
    {
        return 'Records';
    }

    public static function icon(): string
    {
        return 'heroicon-o-trash';
    }

    public static function configSchema(): array
    {
        return [
            'record_source' => ['type' => 'string', 'label' => 'Record Source', 'required' => true],
            'step_node_id' => ['type' => 'string', 'label' => 'Step Node ID', 'required' => false],
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
        ];
    }

    public static function outputSchema(): array
    {
        return [
            'id' => ['type' => 'string', 'label' => 'Record ID'],
            'deleted' => ['type' => 'boolean', 'label' => 'Was Deleted'],
        ];
    }
}
