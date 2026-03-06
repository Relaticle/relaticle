<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Models\WorkflowTemplate;

class TemplateController extends Controller
{
    /**
     * List all active templates, grouped by category.
     */
    public function index(): JsonResponse
    {
        $templates = WorkflowTemplate::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'category', 'icon']);

        $grouped = $templates->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'templates' => $items->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'description' => $t->description,
                    'icon' => $t->icon,
                ])->values(),
            ];
        })->values();

        return response()->json(['categories' => $grouped]);
    }

    /**
     * Get a single template's full definition for applying to a workflow.
     */
    public function show(string $templateId): JsonResponse
    {
        $template = WorkflowTemplate::active()->findOrFail($templateId);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'icon' => $template->icon,
            'definition' => $template->definition,
        ]);
    }
}
