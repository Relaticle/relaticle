<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Forms\Actions;

use Filament\Actions\Action;
use Relaticle\Workflow\Services\FieldResolverService;

class VariablePickerAction extends Action
{
    protected string $targetFieldName = '';

    /**
     * Set the target form field name that this picker should insert variables into.
     * The field name should match the Filament form field's statePath (e.g. 'values_path').
     */
    public function forField(string $field): static
    {
        $this->targetFieldName = $field;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-code-bracket')
            ->tooltip('Insert variable')
            ->modalHeading('Insert Variable')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view('workflow::forms.variable-picker', [
                'groups' => $this->getVariableGroups(),
                'targetField' => $this->targetFieldName,
            ]));
    }

    public static function getDefaultName(): ?string
    {
        return 'variablePicker';
    }

    protected function getVariableGroups(): array
    {
        $livewire = $this->getLivewire();

        $workflowId = $livewire->workflowId ?? null;
        $nodeId = $livewire->selectedNodeId ?? null;

        if (!$workflowId || !$nodeId) {
            return [];
        }

        try {
            $service = app(FieldResolverService::class);
            $rawGroups = $service->getAvailableFields($workflowId, $nodeId);
        } catch (\Throwable) {
            return [];
        }

        // Convert to the Blade view format (uses 'label' for group name, 'path' for field path).
        // The Blade view wraps paths in {{ '{{' . $field['path'] . '}}' }}, so we must strip
        // the braces from fullPath since the view adds them.
        return array_map(fn (array $group) => [
            'label' => $group['group'],
            'fields' => array_map(fn (array $field) => [
                'path' => $this->stripBraces($field['fullPath']),
                'label' => $field['label'],
                'type' => $field['type'],
            ], $group['fields']),
        ], $rawGroups);
    }

    /**
     * Strip the surrounding {{ and }} braces from a fullPath.
     */
    private function stripBraces(string $fullPath): string
    {
        if (str_starts_with($fullPath, '{{') && str_ends_with($fullPath, '}}')) {
            return substr($fullPath, 2, -2);
        }

        return $fullPath;
    }
}
