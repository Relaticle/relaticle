<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Engine\GraphWalker;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\WorkflowManager;

class VariableController extends Controller
{
    public function index(Request $request, string $workflowId): JsonResponse
    {
        $workflow = Workflow::with(['nodes', 'edges'])->findOrFail($workflowId);

        $nodeId = $request->query('node_id');
        if (!$nodeId) {
            return response()->json(['error' => 'node_id query parameter is required'], 422);
        }

        $walker = new GraphWalker($workflow->nodes, $workflow->edges);
        $targetNode = $walker->findNodeByNodeId($nodeId);

        if (!$targetNode) {
            return response()->json(['error' => "Node '{$nodeId}' not found"], 404);
        }

        $groups = $this->buildVariableGroups($workflow, $walker, $targetNode);

        return response()->json(['groups' => $groups]);
    }

    private function buildVariableGroups(Workflow $workflow, GraphWalker $walker, $targetNode): array
    {
        $groups = [];

        // 1. Trigger record fields
        $triggerGroup = $this->buildTriggerGroup($workflow);
        if ($triggerGroup) {
            $groups[] = $triggerGroup;
        }

        // 2. Upstream step outputs
        $predecessors = $walker->getPredecessors($targetNode);
        $manager = app(WorkflowManager::class);
        $actions = $manager->getActions();

        foreach ($predecessors as $predecessor) {
            if ($predecessor->type !== NodeType::Action || !$predecessor->action_type) {
                continue;
            }

            $actionClass = $actions[$predecessor->action_type] ?? null;
            if (!$actionClass) {
                continue;
            }

            $outputSchema = $actionClass::outputSchema();
            if (empty($outputSchema)) {
                continue;
            }

            $prefix = "steps.{$predecessor->node_id}.output";
            $fields = [];
            foreach ($outputSchema as $key => $meta) {
                // Skip internal keys
                if (str_starts_with($key, '_')) {
                    continue;
                }
                $fields[] = [
                    'path' => "{$prefix}.{$key}",
                    'label' => $meta['label'] ?? $key,
                    'type' => $meta['type'] ?? 'string',
                ];
            }

            $groups[] = [
                'label' => sprintf('Step: %s (%s)', $actionClass::label(), $predecessor->node_id),
                'prefix' => $prefix,
                'fields' => $fields,
            ];
        }

        // 3. Built-in variables
        $groups[] = [
            'label' => 'Built-in',
            'prefix' => '',
            'fields' => [
                ['path' => 'now', 'label' => 'Current Timestamp', 'type' => 'datetime'],
                ['path' => 'today', 'label' => 'Today\'s Date', 'type' => 'date'],
            ],
        ];

        return $groups;
    }

    private function buildTriggerGroup(Workflow $workflow): ?array
    {
        $triggerConfig = $workflow->trigger_config ?? [];
        $entityType = $triggerConfig['entity_type'] ?? null;

        if (!$entityType) {
            return null;
        }

        try {
            $schema = app(RelaticleSchema::class);
            $fieldDefs = $schema->getFields($entityType);
        } catch (\Throwable) {
            return null;
        }

        if (empty($fieldDefs)) {
            return null;
        }

        $entity = $schema->getEntity($entityType);
        $label = $entity ? "Trigger Record ({$entity->label})" : 'Trigger Record';

        $fields = [];
        foreach ($fieldDefs as $fieldDef) {
            $path = $fieldDef->isCustomField
                ? "trigger.record.custom.{$fieldDef->key}"
                : "trigger.record.{$fieldDef->key}";

            $fields[] = [
                'path' => $path,
                'label' => $fieldDef->label,
                'type' => $fieldDef->type,
            ];
        }

        return [
            'label' => $label,
            'prefix' => 'trigger.record',
            'fields' => $fields,
        ];
    }
}
