<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Services\FieldResolverService;
use Relaticle\Workflow\Services\TypedFieldResolver;

class VariableController extends Controller
{
    public function index(Request $request, string $workflowId): JsonResponse
    {
        $nodeId = $request->query('node_id');
        if (!$nodeId) {
            return response()->json(['error' => 'node_id query parameter is required'], 422);
        }

        $service = app(FieldResolverService::class);

        try {
            $rawGroups = $service->getAvailableFields($workflowId, $nodeId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Workflow not found'], 404);
        }

        // Convert from FieldResolverService format to existing API format
        $groups = array_map(fn (array $group) => [
            'label' => $group['group'],
            'prefix' => $this->derivePrefixFromGroup($group),
            'fields' => array_map(fn (array $field) => [
                'path' => $this->stripBraces($field['fullPath']),
                'label' => $field['label'],
                'type' => $field['type'],
            ], $group['fields']),
        ], $rawGroups);

        return response()->json(['groups' => $groups]);
    }

    public function typedFields(Request $request, string $workflowId): JsonResponse
    {
        $nodeId = $request->query('node_id');
        $types = $request->query('types') ? explode(',', $request->query('types')) : null;

        if (!$nodeId) {
            return response()->json(['error' => 'node_id is required'], 400);
        }

        try {
            $resolver = app(TypedFieldResolver::class);
            $fields = $resolver->getFieldsByType($workflowId, $nodeId, $types);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Workflow not found'], 404);
        }

        return response()->json(['fields' => $fields]);
    }

    /**
     * Derive the prefix from a FieldResolverService group for backward compatibility.
     *
     * The original VariableController used 'prefix' to indicate the common path prefix
     * for fields in each group (e.g., 'trigger.record', 'steps.action-1.output').
     */
    private function derivePrefixFromGroup(array $group): string
    {
        $groupLabel = $group['group'];

        // Trigger Record groups have prefix 'trigger.record'
        if (str_starts_with($groupLabel, 'Trigger Record')) {
            return 'trigger.record';
        }

        // Step groups: extract prefix from the first field's fullPath
        // e.g., fullPath "{{steps.action-1.output.found}}" -> prefix "steps.action-1.output"
        if (str_starts_with($groupLabel, 'Step:') && !empty($group['fields'])) {
            $firstFieldPath = $this->stripBraces($group['fields'][0]['fullPath']);
            // Remove the last segment (the field key) to get the prefix
            $lastDotPos = strrpos($firstFieldPath, '.');
            if ($lastDotPos !== false) {
                return substr($firstFieldPath, 0, $lastDotPos);
            }
        }

        // Loop Context groups have prefix 'loop'
        if ($groupLabel === 'Loop Context') {
            return 'loop';
        }

        // Built-in and others have empty prefix
        return '';
    }

    /**
     * Strip the surrounding {{ and }} braces from a fullPath.
     *
     * The FieldResolverService returns fullPath with braces (e.g., "{{trigger.record.name}}"),
     * but the existing API format uses paths without braces (e.g., "trigger.record.name").
     */
    private function stripBraces(string $fullPath): string
    {
        if (str_starts_with($fullPath, '{{') && str_ends_with($fullPath, '}}')) {
            return substr($fullPath, 2, -2);
        }

        return $fullPath;
    }
}
