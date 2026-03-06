<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Livewire\Component;
use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Services\TypedFieldResolver;

abstract class BaseAction implements WorkflowAction
{
    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [];
    }

    /**
     * Get the output schema describing what variables this action produces.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function outputSchema(): array
    {
        return [];
    }

    /**
     * Whether this action has side effects (sends emails, creates records, makes HTTP calls, etc.).
     * Actions with side effects are skipped during dry-run mode.
     */
    public static function hasSideEffects(): bool
    {
        return false;
    }

    /**
     * Get the Filament form components for configuring this action.
     *
     * @return array<\Filament\Forms\Components\Field>
     */
    public static function filamentForm(): array
    {
        return [];
    }

    /**
     * Get the action category for the block picker.
     */
    public static function category(): string
    {
        return 'General';
    }

    /**
     * Get the Heroicon name for this action.
     */
    public static function icon(): string
    {
        return 'heroicon-o-bolt';
    }

    /**
     * Get field options from the workflow context for form selects.
     *
     * @param  Component|null  $livewire
     * @param  array<string>|null  $types  Filter by field types (null = all)
     * @return array<string, string>
     */
    protected static function getFieldOptions(?Component $livewire, ?array $types): array
    {
        if (!$livewire || !method_exists($livewire, 'getWorkflowId') || !method_exists($livewire, 'getNodeId')) {
            return [];
        }

        $workflowId = $livewire->getWorkflowId();
        $nodeId = $livewire->getNodeId();

        if (!$workflowId || !$nodeId) {
            return [];
        }

        try {
            $resolver = app(TypedFieldResolver::class);
            $groups = $resolver->getFieldsByType($workflowId, $nodeId, $types);
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                $path = str_replace(['{{', '}}'], '', $field['fullPath']);
                $options[$path] = "[{$group['group']}] {$field['label']}";
            }
        }

        return $options;
    }
}
