<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\WorkflowManager;

class FindRecordAction extends BaseAction
{
    public function execute(array $config, array $context): array
    {
        $entityType = $config['entity_type'] ?? null;

        if (!$entityType) {
            return ['error' => 'entity_type is required', 'found' => false];
        }

        $schema = app(RelaticleSchema::class);
        $entity = $schema->getEntity($entityType);

        if (!$entity) {
            return ['error' => "Unknown entity type: {$entityType}", 'found' => false];
        }

        $modelClass = $entity->modelClass;
        $conditions = $config['conditions'] ?? [];
        $limit = (int) ($config['limit'] ?? 1);

        $query = $modelClass::query();

        // Scope to workflow tenant
        $workflow = $context['_workflow'] ?? null;
        if ($workflow && $workflow->tenant_id) {
            $tenancyConfig = app(WorkflowManager::class)->getTenancyConfig();
            $scopeColumn = $tenancyConfig['scopeColumn'] ?? 'tenant_id';
            $query->where($scopeColumn, $workflow->tenant_id);
        }

        // Apply conditions
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $this->applyCondition($query, $field, $operator, $value);
        }

        $record = $query->limit($limit)->first();

        if (!$record) {
            return [
                'found' => false,
                '_entity_type' => $entityType,
            ];
        }

        return [
            'found' => true,
            'id' => $record->id,
            'record' => $record->toArray(),
            '_entity_type' => $entityType,
        ];
    }

    private function applyCondition($query, string $field, string $operator, mixed $value): void
    {
        match ($operator) {
            'equals' => $query->where($field, '=', $value),
            'not_equals' => $query->where($field, '!=', $value),
            'contains' => $query->where($field, 'like', "%{$value}%"),
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'is_empty' => $query->where(fn ($q) => $q->whereNull($field)->orWhere($field, '')),
            'is_not_empty' => $query->where(fn ($q) => $q->whereNotNull($field)->where($field, '!=', '')),
            default => null,
        };
    }

    public static function label(): string
    {
        return 'Find Record';
    }

    public static function category(): string
    {
        return 'Records';
    }

    public static function icon(): string
    {
        return 'heroicon-o-magnifying-glass';
    }

    public static function configSchema(): array
    {
        return [
            'entity_type' => ['type' => 'string', 'label' => 'Entity Type', 'required' => true],
            'conditions' => ['type' => 'array', 'label' => 'Conditions', 'required' => false],
            'limit' => ['type' => 'integer', 'label' => 'Limit', 'required' => false],
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
            Repeater::make('conditions')
                ->label('Conditions')
                ->schema([
                    TextInput::make('field')
                        ->label('Field')
                        ->required(),
                    Select::make('operator')
                        ->label('Operator')
                        ->options([
                            'equals' => 'Equals',
                            'not_equals' => 'Not Equals',
                            'contains' => 'Contains',
                            'greater_than' => 'Greater Than',
                            'less_than' => 'Less Than',
                            'is_empty' => 'Is Empty',
                            'is_not_empty' => 'Is Not Empty',
                        ])
                        ->required(),
                    TextInput::make('value')
                        ->label('Value'),
                ])
                ->addActionLabel('Add condition')
                ->defaultItems(0),
            TextInput::make('limit')
                ->label('Limit')
                ->numeric()
                ->default(1)
                ->minValue(1),
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

    public static function outputSchema(): array
    {
        return [
            'found' => ['type' => 'boolean', 'label' => 'Record Found'],
            'id' => ['type' => 'string', 'label' => 'Record ID'],
            'record' => ['type' => 'object', 'label' => 'Found Record'],
        ];
    }
}
