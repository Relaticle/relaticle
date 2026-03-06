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

            return null;
        }

        return null;
    }

    public static function label(): string
    {
        return 'Delete Record';
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
