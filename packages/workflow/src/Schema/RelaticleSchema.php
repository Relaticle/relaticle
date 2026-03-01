<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Schema;

use App\Models\Company;
use App\Models\CustomField;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;

class RelaticleSchema
{
    /** @var array<string, EntityDefinition> */
    private array $entities;

    /** @var array<string, array<string, string>> */
    private array $standardFields;

    /** @var array<string, array<string, array{related_entity: string, type: string}>> */
    private array $relationships;

    public function __construct()
    {
        $this->entities = [
            'people' => new EntityDefinition('people', 'People', People::class, 'people'),
            'companies' => new EntityDefinition('companies', 'Companies', Company::class, 'companies'),
            'opportunities' => new EntityDefinition('opportunities', 'Opportunities', Opportunity::class, 'opportunities'),
            'tasks' => new EntityDefinition('tasks', 'Tasks', Task::class, 'tasks'),
            'notes' => new EntityDefinition('notes', 'Notes', Note::class, 'notes'),
        ];

        $this->standardFields = [
            'people' => [
                'name' => 'string',
            ],
            'companies' => [
                'name' => 'string',
                'address' => 'text',
                'country' => 'string',
                'phone' => 'string',
            ],
            'opportunities' => [
                'name' => 'string',
            ],
            'tasks' => [
                'title' => 'string',
            ],
            'notes' => [
                'title' => 'string',
            ],
        ];

        $this->relationships = [
            'people' => [
                'company' => ['related_entity' => 'companies', 'type' => 'belongs_to'],
            ],
            'opportunities' => [
                'company' => ['related_entity' => 'companies', 'type' => 'belongs_to'],
                'contact' => ['related_entity' => 'people', 'type' => 'belongs_to'],
            ],
        ];
    }

    /**
     * @return array<string, EntityDefinition>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getEntity(string $key): ?EntityDefinition
    {
        return $this->entities[$key] ?? null;
    }

    /**
     * @return FieldDefinition[]
     */
    public function getFields(string $entityKey): array
    {
        $fields = [];

        // Standard fields
        foreach ($this->standardFields[$entityKey] ?? [] as $fieldKey => $type) {
            $fields[] = new FieldDefinition(
                key: $fieldKey,
                label: str($fieldKey)->title()->replace('_', ' ')->toString(),
                type: $type,
                isCustomField: false,
            );
        }

        // Relationship foreign keys as fields
        foreach ($this->relationships[$entityKey] ?? [] as $relName => $relDef) {
            $foreignKey = $relName . '_id';
            $relatedEntity = $this->entities[$relDef['related_entity']] ?? null;
            $fields[] = new FieldDefinition(
                key: $foreignKey,
                label: ($relatedEntity?->label ?? str($relName)->title()->toString()),
                type: 'relation',
                isCustomField: false,
            );
        }

        // Custom fields from database
        $entity = $this->getEntity($entityKey);
        if ($entity && class_exists(CustomField::class)) {
            try {
                $customFields = CustomField::query()
                    ->forEntity($entity->modelClass)
                    ->active()
                    ->get();

                foreach ($customFields as $customField) {
                    $options = [];
                    if ($customField->typeData?->dataType?->isChoiceField()) {
                        $options = $customField->options
                            ->map(fn ($opt) => ['value' => $opt->id, 'label' => $opt->name])
                            ->toArray();
                    }

                    $fields[] = new FieldDefinition(
                        key: $customField->code,
                        label: $customField->name,
                        type: $customField->type,
                        isCustomField: true,
                        customFieldId: $customField->id,
                        options: $options,
                    );
                }
            } catch (\Throwable) {
                // Custom fields table may not exist in test environments
            }
        }

        return $fields;
    }

    /**
     * @return array<string, array{related_entity: string, type: string}>
     */
    public function getRelationships(string $entityKey): array
    {
        return $this->relationships[$entityKey] ?? [];
    }
}
