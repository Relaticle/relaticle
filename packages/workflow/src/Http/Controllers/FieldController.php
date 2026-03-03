<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Services\FieldResolverService;

class FieldController extends Controller
{
    public function availableFields(Request $request, string $workflowId, string $nodeId): JsonResponse
    {
        $service = app(FieldResolverService::class);
        $groups = $service->getAvailableFields($workflowId, $nodeId);

        return response()->json(['groups' => $groups]);
    }

    public function entityFields(Request $request, string $entityType): JsonResponse
    {
        $service = app(FieldResolverService::class);
        $fields = $service->getEntityFields($entityType);

        return response()->json(['fields' => $fields]);
    }

    public function upstreamSteps(Request $request, string $workflowId, string $nodeId): JsonResponse
    {
        $service = app(FieldResolverService::class);
        $steps = $service->getUpstreamStepNodes($workflowId, $nodeId);

        return response()->json(['steps' => $steps]);
    }
}
