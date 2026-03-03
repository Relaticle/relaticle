<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Services;

use Relaticle\Workflow\Engine\GraphWalker;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\WorkflowManager;

class FieldResolverService
{
    public function __construct(
        private readonly RelaticleSchema $schema,
        private readonly WorkflowManager $manager,
    ) {}

    /**
     * Get all available fields at a given node position in the workflow graph.
     *
     * Returns grouped fields: trigger record fields, upstream step outputs,
     * loop context (if inside a loop), and built-in variables.
     *
     * @return array<int, array{group: string, fields: array<int, array{key: string, label: string, type: string, fullPath: string}>}>
     */
    public function getAvailableFields(string $workflowId, string $nodeId): array
    {
        $workflow = Workflow::with(['nodes', 'edges'])->findOrFail($workflowId);

        $walker = new GraphWalker($workflow->nodes, $workflow->edges);
        $targetNode = $walker->findNodeByNodeId($nodeId);

        if (!$targetNode) {
            return [];
        }

        $groups = [];

        // 1. Trigger record fields
        $triggerGroup = $this->buildTriggerFieldGroup($workflow);
        if ($triggerGroup !== null) {
            $groups[] = $triggerGroup;
        }

        // 2. Upstream step output fields
        $predecessors = $walker->getPredecessors($targetNode);
        $actions = $this->manager->getActions();

        $hasLoopPredecessor = false;

        foreach ($predecessors as $predecessor) {
            // Track whether any predecessor is a Loop node
            if ($predecessor->type === NodeType::Loop) {
                $hasLoopPredecessor = true;
            }

            if ($predecessor->type !== NodeType::Action || !$predecessor->action_type) {
                continue;
            }

            $actionClass = $actions[$predecessor->action_type] ?? null;
            if ($actionClass === null) {
                continue;
            }

            $outputSchema = $actionClass::outputSchema();
            if (empty($outputSchema)) {
                continue;
            }

            $fields = [];
            foreach ($outputSchema as $key => $meta) {
                // Skip internal keys (starting with underscore)
                if (str_starts_with($key, '_')) {
                    continue;
                }

                $fields[] = [
                    'key' => $key,
                    'label' => $meta['label'] ?? $key,
                    'type' => $meta['type'] ?? 'string',
                    'fullPath' => "{{steps.{$predecessor->node_id}.output.{$key}}}",
                ];
            }

            if (!empty($fields)) {
                $groups[] = [
                    'group' => sprintf('Step: %s (%s)', $actionClass::label(), $predecessor->node_id),
                    'fields' => $fields,
                ];
            }
        }

        // 3. Loop context (if any predecessor is a Loop node)
        if ($hasLoopPredecessor) {
            $groups[] = [
                'group' => 'Loop Context',
                'fields' => [
                    [
                        'key' => 'item',
                        'label' => 'Current Item',
                        'type' => 'mixed',
                        'fullPath' => '{{loop.item}}',
                    ],
                    [
                        'key' => 'index',
                        'label' => 'Current Index',
                        'type' => 'integer',
                        'fullPath' => '{{loop.index}}',
                    ],
                ],
            ];
        }

        // 4. Built-in variables
        $groups[] = [
            'group' => 'Built-in',
            'fields' => [
                [
                    'key' => 'now',
                    'label' => 'Current Timestamp',
                    'type' => 'datetime',
                    'fullPath' => '{{now}}',
                ],
                [
                    'key' => 'today',
                    'label' => "Today's Date",
                    'type' => 'date',
                    'fullPath' => '{{today}}',
                ],
            ],
        ];

        return $groups;
    }

    /**
     * Get fields for a specific entity type (for record action form dropdowns).
     *
     * @return array<int, array{key: string, label: string, type: string, isCustom: bool, group: string, options?: array}>
     */
    public function getEntityFields(string $entityType): array
    {
        $fieldDefinitions = $this->schema->getFields($entityType);

        $fields = [];
        foreach ($fieldDefinitions as $fieldDef) {
            $field = [
                'key' => $fieldDef->key,
                'label' => $fieldDef->label,
                'type' => $fieldDef->type,
                'isCustom' => $fieldDef->isCustomField,
                'group' => $fieldDef->isCustomField ? 'Custom Fields' : 'Standard Fields',
            ];

            if (!empty($fieldDef->options)) {
                $field['options'] = $fieldDef->options;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Get upstream action nodes (for step_node_id dropdowns).
     *
     * @return array<int, array{node_id: string, label: string, actionType: string}>
     */
    public function getUpstreamStepNodes(string $workflowId, string $nodeId): array
    {
        $workflow = Workflow::with(['nodes', 'edges'])->findOrFail($workflowId);

        $walker = new GraphWalker($workflow->nodes, $workflow->edges);
        $targetNode = $walker->findNodeByNodeId($nodeId);

        if (!$targetNode) {
            return [];
        }

        $predecessors = $walker->getPredecessors($targetNode);
        $actions = $this->manager->getActions();

        $stepNodes = [];
        foreach ($predecessors as $predecessor) {
            if ($predecessor->type !== NodeType::Action || !$predecessor->action_type) {
                continue;
            }

            $actionClass = $actions[$predecessor->action_type] ?? null;
            if ($actionClass === null) {
                continue;
            }

            $stepNodes[] = [
                'node_id' => $predecessor->node_id,
                'label' => $actionClass::label(),
                'actionType' => $predecessor->action_type,
            ];
        }

        return $stepNodes;
    }

    /**
     * Build the trigger record field group from the workflow's trigger configuration.
     *
     * @return array{group: string, fields: array<int, array{key: string, label: string, type: string, fullPath: string}>}|null
     */
    private function buildTriggerFieldGroup(Workflow $workflow): ?array
    {
        $triggerConfig = $workflow->trigger_config ?? [];
        $entityType = $triggerConfig['entity_type'] ?? null;

        if (!$entityType) {
            return null;
        }

        try {
            $fieldDefinitions = $this->schema->getFields($entityType);
        } catch (\Throwable) {
            return null;
        }

        if (empty($fieldDefinitions)) {
            return null;
        }

        $entity = $this->schema->getEntity($entityType);
        $groupLabel = $entity !== null
            ? "Trigger Record ({$entity->label})"
            : 'Trigger Record';

        $fields = [];
        foreach ($fieldDefinitions as $fieldDef) {
            $pathPrefix = $fieldDef->isCustomField
                ? "trigger.record.custom.{$fieldDef->key}"
                : "trigger.record.{$fieldDef->key}";

            $fields[] = [
                'key' => $fieldDef->key,
                'label' => $fieldDef->label,
                'type' => $fieldDef->type,
                'fullPath' => "{{{$pathPrefix}}}",
            ];
        }

        return [
            'group' => $groupLabel,
            'fields' => $fields,
        ];
    }
}
