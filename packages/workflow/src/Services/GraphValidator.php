<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Services;

class GraphValidator
{
    public function __construct(
        private readonly BlockMetadataProvider $metadataProvider,
    ) {}

    /**
     * Validate a workflow graph for structural correctness.
     *
     * Checks performed:
     * 1. Invalid connections (source type not allowed to connect to target type)
     * 2. Max outgoing edges exceeded per block rules
     * 3. Cycle detection via DFS (white/gray/black coloring)
     * 4. Disconnected nodes (no incoming edges, not a trigger/root)
     * 5. Dead-end nodes (no outgoing edges, not a stop/terminal)
     * 6. Missing required config fields for action nodes
     *
     * @param  list<array{node_id: string, type: string, action_type: string|null, config: array}>  $nodes
     * @param  list<array{source_node_id: string, target_node_id: string}>  $edges
     * @return array{errors: list<array{type: string, nodeId: string, message: string}>, warnings: list<array{type: string, nodeId: string, message: string}>}
     */
    public function validate(array $nodes, array $edges): array
    {
        $errors = [];
        $warnings = [];

        $manifest = $this->metadataProvider->getManifest();
        $blockRules = $manifest['blocks'];
        $actionMetadata = $manifest['actions'];

        // Index nodes by node_id for quick lookup
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['node_id']] = $node;
        }

        // Build adjacency lists and count outgoing/incoming edges
        $outgoing = [];   // node_id => [target_node_id, ...]
        $incoming = [];   // node_id => [source_node_id, ...]
        foreach ($nodes as $node) {
            $outgoing[$node['node_id']] = [];
            $incoming[$node['node_id']] = [];
        }

        foreach ($edges as $edge) {
            $sourceId = $edge['source_node_id'];
            $targetId = $edge['target_node_id'];

            if (! isset($nodeMap[$sourceId]) || ! isset($nodeMap[$targetId])) {
                continue; // skip edges referencing unknown nodes
            }

            $outgoing[$sourceId][] = $targetId;
            $incoming[$targetId][] = $sourceId;
        }

        // 1. Check invalid connections
        $errors = array_merge($errors, $this->checkInvalidConnections($edges, $nodeMap, $blockRules));

        // 2. Check max outgoing edges
        $errors = array_merge($errors, $this->checkMaxOutgoing($outgoing, $nodeMap, $blockRules));

        // 3. Cycle detection
        $errors = array_merge($errors, $this->detectCycles($outgoing, $nodeMap));

        // 4. Disconnected nodes (no incoming, not root)
        $warnings = array_merge($warnings, $this->checkDisconnectedNodes($incoming, $outgoing, $nodeMap, $blockRules));

        // 5. Dead-end nodes (no outgoing, not terminal)
        $warnings = array_merge($warnings, $this->checkDeadEndNodes($outgoing, $nodeMap, $blockRules));

        // 6. Missing required config fields
        $warnings = array_merge($warnings, $this->checkRequiredConfig($nodeMap, $actionMetadata));

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check each edge against the connection rules from block metadata.
     *
     * @param  list<array{source_node_id: string, target_node_id: string}>  $edges
     * @param  array<string, array>  $nodeMap
     * @param  array<string, array>  $blockRules
     * @return list<array{type: string, nodeId: string, message: string}>
     */
    private function checkInvalidConnections(array $edges, array $nodeMap, array $blockRules): array
    {
        $errors = [];

        foreach ($edges as $edge) {
            $sourceId = $edge['source_node_id'];
            $targetId = $edge['target_node_id'];

            if (! isset($nodeMap[$sourceId]) || ! isset($nodeMap[$targetId])) {
                continue;
            }

            $sourceType = $nodeMap[$sourceId]['type'];
            $targetType = $nodeMap[$targetId]['type'];

            $sourceRules = $blockRules[$sourceType] ?? null;
            if ($sourceRules === null) {
                continue;
            }

            // Check if target type is in allowedTargets of source
            $allowedTargets = $sourceRules['allowedTargets'] ?? [];
            if (! in_array($targetType, $allowedTargets, true)) {
                $errors[] = [
                    'type' => 'invalid_connection',
                    'nodeId' => $sourceId,
                    'message' => "'{$sourceType}' block cannot connect to '{$targetType}' block.",
                ];
            }
        }

        return $errors;
    }

    /**
     * Check that no node exceeds its maxOutgoing limit.
     *
     * @param  array<string, list<string>>  $outgoing
     * @param  array<string, array>  $nodeMap
     * @param  array<string, array>  $blockRules
     * @return list<array{type: string, nodeId: string, message: string}>
     */
    private function checkMaxOutgoing(array $outgoing, array $nodeMap, array $blockRules): array
    {
        $errors = [];

        foreach ($outgoing as $nodeId => $targets) {
            $nodeType = $nodeMap[$nodeId]['type'] ?? null;
            if ($nodeType === null) {
                continue;
            }

            $rules = $blockRules[$nodeType] ?? null;
            if ($rules === null) {
                continue;
            }

            $maxOutgoing = $rules['maxOutgoing'];
            if ($maxOutgoing !== null && count($targets) > $maxOutgoing) {
                $errors[] = [
                    'type' => 'max_outgoing_exceeded',
                    'nodeId' => $nodeId,
                    'message' => "'{$nodeType}' block has " . count($targets) . " outgoing connections (max: {$maxOutgoing}).",
                ];
            }
        }

        return $errors;
    }

    /**
     * Detect cycles in the graph using DFS with white/gray/black coloring.
     *
     * WHITE (0) = unvisited, GRAY (1) = in current DFS path, BLACK (2) = fully processed.
     * A cycle is detected when we encounter a GRAY node during traversal.
     *
     * @param  array<string, list<string>>  $outgoing
     * @param  array<string, array>  $nodeMap
     * @return list<array{type: string, nodeId: string, message: string}>
     */
    private function detectCycles(array $outgoing, array $nodeMap): array
    {
        $errors = [];

        $white = 0;
        $gray = 1;
        $black = 2;

        $color = [];
        foreach ($nodeMap as $nodeId => $node) {
            $color[$nodeId] = $white;
        }

        $dfs = function (string $nodeId) use (&$dfs, &$color, $outgoing, $gray, $black, &$errors): void {
            $color[$nodeId] = $gray;

            foreach ($outgoing[$nodeId] ?? [] as $targetId) {
                if (($color[$targetId] ?? $black) === $gray) {
                    // Found a back-edge => cycle
                    $errors[] = [
                        'type' => 'cycle',
                        'nodeId' => $targetId,
                        'message' => "Cycle detected involving node '{$targetId}'.",
                    ];
                } elseif (($color[$targetId] ?? $black) === 0) { // white
                    $dfs($targetId);
                }
            }

            $color[$nodeId] = $black;
        };

        foreach ($nodeMap as $nodeId => $node) {
            if ($color[$nodeId] === $white) {
                $dfs($nodeId);
            }
        }

        return $errors;
    }

    /**
     * Find disconnected nodes: nodes with no incoming and no outgoing edges, excluding root nodes.
     *
     * @param  array<string, list<string>>  $incoming
     * @param  array<string, list<string>>  $outgoing
     * @param  array<string, array>  $nodeMap
     * @param  array<string, array>  $blockRules
     * @return list<array{type: string, nodeId: string, message: string}>
     */
    private function checkDisconnectedNodes(array $incoming, array $outgoing, array $nodeMap, array $blockRules): array
    {
        $warnings = [];

        foreach ($nodeMap as $nodeId => $node) {
            $nodeType = $node['type'];
            $rules = $blockRules[$nodeType] ?? null;

            // Root nodes (triggers) don't need incoming edges
            if ($rules !== null && ($rules['isRoot'] ?? false)) {
                continue;
            }

            $hasIncoming = ! empty($incoming[$nodeId] ?? []);

            if (! $hasIncoming) {
                $warnings[] = [
                    'type' => 'disconnected',
                    'nodeId' => $nodeId,
                    'message' => 'This block is not connected to the workflow',
                ];
            }
        }

        return $warnings;
    }

    /**
     * Find dead-end nodes: nodes with no outgoing edges that are not terminal blocks.
     *
     * @param  array<string, list<string>>  $outgoing
     * @param  array<string, array>  $nodeMap
     * @param  array<string, array>  $blockRules
     * @return list<array{type: string, nodeId: string, message: string}>
     */
    private function checkDeadEndNodes(array $outgoing, array $nodeMap, array $blockRules): array
    {
        $warnings = [];

        foreach ($outgoing as $nodeId => $targets) {
            $nodeType = $nodeMap[$nodeId]['type'] ?? null;
            if ($nodeType === null) {
                continue;
            }

            $rules = $blockRules[$nodeType] ?? null;

            // Terminal nodes (stop) are expected to have no outgoing
            if ($rules !== null && ($rules['isTerminal'] ?? false)) {
                continue;
            }

            if (empty($targets)) {
                $warnings[] = [
                    'type' => 'dead_end',
                    'nodeId' => $nodeId,
                    'message' => "Node '{$nodeId}' has no outgoing connections and is not a stop block.",
                ];
            }
        }

        return $warnings;
    }

    /**
     * Check action nodes for missing required configuration fields.
     *
     * @param  array<string, array>  $nodeMap
     * @param  array<string, array{category: string, requiredConfig: list<string>, inheritsEntityFromTrigger: bool}>  $actionMetadata
     * @return list<array{type: string, nodeId: string, message: string}>
     */
    private function checkRequiredConfig(array $nodeMap, array $actionMetadata): array
    {
        $warnings = [];

        foreach ($nodeMap as $nodeId => $node) {
            if ($node['type'] !== 'action') {
                continue;
            }

            $actionType = $node['action_type'] ?? null;
            if ($actionType === null) {
                continue;
            }

            $meta = $actionMetadata[$actionType] ?? null;
            if ($meta === null) {
                continue;
            }

            $requiredFields = $meta['requiredConfig'] ?? [];
            $config = $node['config'] ?? [];

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (! isset($config[$field]) || $config[$field] === '' || $config[$field] === null) {
                    $missingFields[] = $field;
                }
            }

            if (! empty($missingFields)) {
                $warnings[] = [
                    'type' => 'missing_required_config',
                    'nodeId' => $nodeId,
                    'message' => "Action '{$actionType}' is missing required config: " . implode(', ', $missingFields) . '.',
                ];
            }
        }

        return $warnings;
    }
}
