<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Forms\Actions;

use Filament\Actions\Action;
use Relaticle\Workflow\Engine\GraphWalker;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowNode;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\WorkflowManager;

class VariablePickerAction extends Action
{
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

        if (! $workflowId || ! $nodeId) {
            return [];
        }

        $workflow = Workflow::with(['nodes', 'edges'])->find($workflowId);
        if (! $workflow) {
            return [];
        }

        $groups = [];

        // 1. Trigger record fields
        $triggerNode = $workflow->nodes->first(fn ($n) => $n->type->value === 'trigger');
        if ($triggerNode) {
            $entityType = $triggerNode->config['entity_type'] ?? null;
            $triggerFields = [];

            if ($entityType) {
                try {
                    $schema = app(RelaticleSchema::class);
                    $fields = $schema->getFields($entityType);

                    foreach ($fields as $field) {
                        $prefix = $field->isCustomField ? 'trigger.record.custom.' : 'trigger.record.';
                        $triggerFields[] = [
                            'path' => $prefix . $field->key,
                            'label' => $field->label,
                            'type' => $field->type,
                        ];
                    }
                } catch (\Throwable) {
                    // Schema may not be available in all contexts
                }
            }

            if (! empty($triggerFields)) {
                $groups[] = [
                    'label' => 'Trigger Record',
                    'fields' => $triggerFields,
                ];
            }
        }

        // 2. Upstream step outputs
        $currentNode = $workflow->nodes->first(fn ($n) => $n->node_id === $nodeId);
        if ($currentNode) {
            try {
                $walker = new GraphWalker($workflow->nodes, $workflow->edges);
                $predecessors = $walker->getPredecessors($currentNode);
                $manager = app(WorkflowManager::class);
                $actions = $manager->getActions();

                foreach ($predecessors as $pred) {
                    if ($pred->type->value !== 'action' || ! $pred->action_type) {
                        continue;
                    }

                    $actionClass = $actions[$pred->action_type] ?? null;
                    if (! $actionClass) {
                        continue;
                    }

                    $outputSchema = $actionClass::outputSchema();
                    if (empty($outputSchema)) {
                        continue;
                    }

                    $stepFields = [];
                    foreach ($outputSchema as $key => $def) {
                        $stepFields[] = [
                            'path' => "steps.{$pred->node_id}.output.{$key}",
                            'label' => $def['label'] ?? $key,
                            'type' => $def['type'] ?? 'string',
                        ];
                    }

                    $groups[] = [
                        'label' => "Step: {$actionClass::label()} ({$pred->node_id})",
                        'fields' => $stepFields,
                    ];
                }
            } catch (\Throwable) {
                // GraphWalker may fail if nodes/edges are incomplete
            }
        }

        // 3. Built-in variables
        $groups[] = [
            'label' => 'Built-in',
            'fields' => [
                ['path' => 'now', 'label' => 'Current Timestamp', 'type' => 'datetime'],
                ['path' => 'today', 'label' => "Today's Date", 'type' => 'date'],
            ],
        ];

        return $groups;
    }
}
