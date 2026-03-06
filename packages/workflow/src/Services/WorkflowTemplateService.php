<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowTemplate;

class WorkflowTemplateService
{
    /**
     * Instantiate a template into a new Workflow for the given tenant and user.
     *
     * @param  array<string, mixed>  $overrides  Optional field overrides (e.g. custom name)
     */
    public function createFromTemplate(
        WorkflowTemplate $template,
        string $tenantId,
        ?string $creatorId = null,
        array $overrides = [],
    ): Workflow {
        $definition = $template->definition;

        return DB::transaction(function () use ($template, $tenantId, $creatorId, $definition, $overrides): Workflow {
            $triggerType = TriggerType::tryFrom($definition['trigger_type'] ?? 'manual') ?? TriggerType::Manual;

            /** @var Workflow $workflow */
            $workflow = Workflow::create(array_merge([
                'name' => $overrides['name'] ?? $template->name,
                'description' => $template->description,
                'trigger_type' => $triggerType,
                'trigger_config' => $definition['trigger_config'] ?? [],
                'status' => WorkflowStatus::Draft,
                'tenant_id' => $tenantId,
                'creator_id' => $creatorId,
            ], $overrides));

            // Create nodes, collecting logical_node_id → DB ULID map
            /** @var array<string, string> $nodeIdMap */
            $nodeIdMap = [];

            foreach ($definition['nodes'] ?? [] as $nodeDef) {
                $node = $workflow->nodes()->create([
                    'node_id' => $nodeDef['node_id'],
                    'type' => $nodeDef['type'],
                    'action_type' => $nodeDef['action_type'] ?? null,
                    'config' => $nodeDef['config'] ?? [],
                    'position_x' => $nodeDef['position_x'] ?? 0,
                    'position_y' => $nodeDef['position_y'] ?? 0,
                ]);

                $nodeIdMap[$nodeDef['node_id']] = $node->id;
            }

            // Create edges using resolved DB ULIDs
            foreach ($definition['edges'] ?? [] as $edgeDef) {
                $sourceId = $nodeIdMap[$edgeDef['source']] ?? null;
                $targetId = $nodeIdMap[$edgeDef['target']] ?? null;

                if ($sourceId === null || $targetId === null) {
                    continue;
                }

                $workflow->edges()->create([
                    'edge_id' => $edgeDef['edge_id'],
                    'source_node_id' => $sourceId,
                    'target_node_id' => $targetId,
                    'condition_label' => $edgeDef['condition_label'] ?? null,
                    'condition_config' => $edgeDef['condition_config'] ?? null,
                ]);
            }

            return $workflow;
        });
    }
}
