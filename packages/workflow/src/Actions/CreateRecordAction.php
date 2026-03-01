<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

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
        $fieldMappings = $config['field_mappings'] ?? [];
        $customFieldMappings = $config['custom_field_mappings'] ?? [];

        // Set tenant and creator from workflow context
        $workflow = $context['_workflow'] ?? null;
        $attributes = $fieldMappings;

        if ($workflow) {
            $tenancyConfig = app(WorkflowManager::class)->getTenancyConfig();
            $scopeColumn = $tenancyConfig['scopeColumn'] ?? 'tenant_id';
            $attributes[$scopeColumn] = $workflow->tenant_id ?? ($attributes[$scopeColumn] ?? null);
            $attributes['created_by'] = $workflow->creator_id ?? ($attributes['created_by'] ?? null);
        }

        // Set creation source to SYSTEM for workflow-created records
        if (class_exists(\App\Enums\CreationSource::class)) {
            $attributes['creation_source'] = \App\Enums\CreationSource::SYSTEM;
        }

        $record = $modelClass::create($attributes);

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

    public static function configSchema(): array
    {
        return [
            'entity_type' => ['type' => 'string', 'label' => 'Entity Type', 'required' => true],
            'field_mappings' => ['type' => 'object', 'label' => 'Field Mappings', 'required' => true],
            'custom_field_mappings' => ['type' => 'object', 'label' => 'Custom Field Mappings', 'required' => false],
        ];
    }

    public static function outputSchema(): array
    {
        return [
            'id' => ['type' => 'string', 'label' => 'Record ID'],
            'created' => ['type' => 'boolean', 'label' => 'Was Created'],
        ];
    }
}
