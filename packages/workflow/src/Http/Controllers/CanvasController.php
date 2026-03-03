<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Services\BlockMetadataProvider;
use Relaticle\Workflow\Services\GraphValidator;
use Relaticle\Workflow\WorkflowManager;

class CanvasController extends Controller
{
    /**
     * Load canvas data with nodes, edges, and sidebar meta.
     */
    public function show(string $workflowId, WorkflowManager $manager): JsonResponse
    {
        $workflow = Workflow::with(['nodes', 'edges'])->findOrFail($workflowId);

        $nodes = $workflow->nodes->map(fn ($node) => [
            'node_id' => $node->node_id,
            'type' => $node->type->value,
            'action_type' => $node->action_type,
            'config' => $node->config,
            'position_x' => $node->position_x,
            'position_y' => $node->position_y,
        ])->values();

        // Build a map of ULID -> node_id for edge resolution
        $nodeIdMap = $workflow->nodes->pluck('node_id', 'id');

        $edges = $workflow->edges->map(fn ($edge) => [
            'edge_id' => $edge->edge_id,
            'source_node_id' => $nodeIdMap[$edge->source_node_id] ?? $edge->source_node_id,
            'target_node_id' => $nodeIdMap[$edge->target_node_id] ?? $edge->target_node_id,
            'condition_label' => $edge->condition_label,
            'condition_config' => $edge->condition_config,
        ])->values();

        // Build meta information for the sidebar
        $models = collect($manager->getTriggerableModels())
            ->map(fn ($config, $class) => [
                'class' => $class,
                'label' => $config['label'],
                'events' => $config['events'],
            ])
            ->values()
            ->toArray();

        $actions = collect($manager->getRegisteredActions())
            ->map(fn ($class, $key) => [
                'key' => $key,
                'label' => $class::label(),
                'category' => $class::category(),
                'icon' => $class::icon(),
                'configSchema' => $class::configSchema(),
            ])
            ->values()
            ->toArray();

        // Also build a keyed map for quick lookup in the builder
        $registeredActions = [];
        foreach ($manager->getRegisteredActions() as $key => $class) {
            $registeredActions[$key] = [
                'label' => $class::label(),
                'category' => $class::category(),
                'icon' => $class::icon(),
                'configSchema' => $class::configSchema(),
                'outputSchema' => $class::outputSchema(),
            ];
        }

        $metadataProvider = app(BlockMetadataProvider::class);

        return response()->json([
            'canvas_data' => $workflow->canvas_data,
            'canvas_version' => $workflow->canvas_version,
            'nodes' => $nodes,
            'edges' => $edges,
            'manifest' => $metadataProvider->getManifest(),
            'meta' => [
                'models' => $models,
                'actions' => $actions,
                'registered_actions' => $registeredActions,
                'description' => $workflow->description,
                'trigger_type' => $workflow->trigger_type?->value,
                'trigger_config' => $workflow->trigger_config ?? [],
                'trigger_outputs' => [
                    'record_created' => [
                        'record' => ['type' => 'object', 'label' => 'Created Record'],
                        'event' => ['type' => 'string', 'label' => 'Event Name'],
                    ],
                    'record_updated' => [
                        'record' => ['type' => 'object', 'label' => 'Updated Record'],
                        'event' => ['type' => 'string', 'label' => 'Event Name'],
                    ],
                    'record_deleted' => [
                        'record' => ['type' => 'object', 'label' => 'Deleted Record'],
                        'event' => ['type' => 'string', 'label' => 'Event Name'],
                    ],
                    'manual' => [
                        'context' => ['type' => 'object', 'label' => 'Manual Context'],
                    ],
                    'webhook' => [
                        'webhook' => ['type' => 'object', 'label' => 'Webhook Payload'],
                    ],
                    'scheduled' => [
                        'tenant_id' => ['type' => 'string', 'label' => 'Tenant ID'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Save canvas data and sync nodes/edges to the database.
     */
    public function update(Request $request, string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);

        // Handle partial saves (name, description, settings)
        if (! $request->has('nodes')) {
            $updates = [];
            if ($request->has('name')) {
                $updates['name'] = $request->input('name');
            }
            if ($request->has('description')) {
                $updates['description'] = $request->input('description');
            }
            if ($request->has('settings')) {
                $triggerConfig = $workflow->trigger_config ?? [];
                $settings = $request->input('settings', []);
                if (isset($settings['max_steps'])) {
                    $triggerConfig['max_steps'] = min((int) $settings['max_steps'], 1000);
                }
                if (isset($settings['notify_on_failure'])) {
                    $triggerConfig['notify_on_failure'] = (bool) $settings['notify_on_failure'];
                }
                $updates['trigger_config'] = $triggerConfig;
            }
            if (! empty($updates)) {
                $workflow->update($updates);
            }

            return response()->json(['message' => 'Updated successfully.']);
        }

        $validated = $request->validate([
            'canvas_data' => ['present', 'array'],
            'canvas_version' => ['nullable', 'integer'],
            'nodes' => ['present', 'array'],
            'nodes.*.node_id' => ['required', 'string'],
            'nodes.*.type' => ['required', 'string', Rule::enum(NodeType::class)],
            'nodes.*.action_type' => ['nullable', 'string'],
            'nodes.*.config' => ['nullable', 'array'],
            'nodes.*.position_x' => ['nullable', 'integer'],
            'nodes.*.position_y' => ['nullable', 'integer'],
            'edges' => ['present', 'array'],
            'edges.*.edge_id' => ['required', 'string'],
            'edges.*.source_node_id' => ['required', 'string'],
            'edges.*.target_node_id' => ['required', 'string'],
            'edges.*.condition_label' => ['nullable', 'string'],
            'edges.*.condition_config' => ['nullable', 'array'],
        ]);

        // Validate edge references point to nodes in the payload
        $nodeIds = collect($validated['nodes'])->pluck('node_id')->all();
        foreach ($validated['edges'] as $edge) {
            if (! in_array($edge['source_node_id'], $nodeIds, true)) {
                return response()->json([
                    'error' => "Edge '{$edge['edge_id']}' references non-existent source node '{$edge['source_node_id']}'.",
                ], 422);
            }
            if (! in_array($edge['target_node_id'], $nodeIds, true)) {
                return response()->json([
                    'error' => "Edge '{$edge['edge_id']}' references non-existent target node '{$edge['target_node_id']}'.",
                ], 422);
            }
        }

        // Validate graph structure (cycles, invalid connections, etc.)
        $graphValidator = app(GraphValidator::class);
        $validationResult = $graphValidator->validate(
            $validated['nodes'],
            $validated['edges']
        );

        if (! empty($validationResult['errors'])) {
            return response()->json([
                'message' => 'Graph validation failed',
                'validation' => $validationResult,
            ], 422);
        }

        try {
            return DB::transaction(function () use ($workflow, $validated) {
                // Lock the row for the duration of the transaction
                $lockedWorkflow = Workflow::lockForUpdate()->findOrFail($workflow->id);

                // Optimistic lock check — inside the transaction to prevent TOCTOU race
                if (isset($validated['canvas_version']) && $validated['canvas_version'] !== $lockedWorkflow->canvas_version) {
                    return response()->json([
                        'error' => 'Canvas has been modified by another user. Please refresh and try again.',
                    ], 409);
                }

                // 1. Update canvas_data on the workflow
                $lockedWorkflow->update(['canvas_data' => $validated['canvas_data']]);

                // 2. Sync nodes: upsert by node_id, delete absent nodes
                $incomingNodeIds = collect($validated['nodes'])->pluck('node_id')->toArray();

                // Delete nodes that are no longer in the payload
                $lockedWorkflow->nodes()
                    ->whereNotIn('node_id', $incomingNodeIds)
                    ->delete();

                // Upsert each node
                foreach ($validated['nodes'] as $nodeData) {
                    $lockedWorkflow->nodes()->updateOrCreate(
                        ['node_id' => $nodeData['node_id']],
                        [
                            'type' => $nodeData['type'],
                            'action_type' => $nodeData['action_type'] ?? null,
                            'config' => $nodeData['config'] ?? null,
                            'position_x' => $nodeData['position_x'] ?? 0,
                            'position_y' => $nodeData['position_y'] ?? 0,
                        ]
                    );
                }

                // 3. Sync edges: map node_id strings to ULID IDs, then upsert
                // Refresh nodes to get the latest ULID mappings
                $nodeMap = $lockedWorkflow->nodes()->pluck('id', 'node_id');

                $incomingEdgeIds = collect($validated['edges'])->pluck('edge_id')->toArray();

                // Delete edges that are no longer in the payload
                $lockedWorkflow->edges()
                    ->whereNotIn('edge_id', $incomingEdgeIds)
                    ->delete();

                // Upsert each edge
                foreach ($validated['edges'] as $edgeData) {
                    $sourceId = $nodeMap[$edgeData['source_node_id']] ?? null;
                    $targetId = $nodeMap[$edgeData['target_node_id']] ?? null;

                    if ($sourceId === null || $targetId === null) {
                        continue; // Skip edges referencing non-existent nodes
                    }

                    $lockedWorkflow->edges()->updateOrCreate(
                        ['edge_id' => $edgeData['edge_id']],
                        [
                            'source_node_id' => $sourceId,
                            'target_node_id' => $targetId,
                            'condition_label' => $edgeData['condition_label'] ?? null,
                            'condition_config' => $edgeData['condition_config'] ?? null,
                        ]
                    );
                }
                // Increment version inside the transaction for atomicity
                $lockedWorkflow->increment('canvas_version');

                return response()->json([
                    'message' => 'Canvas saved successfully.',
                    'canvas_version' => $lockedWorkflow->canvas_version,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Canvas save failed', [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to save canvas. Please try again.',
            ], 500);
        }
    }
}
