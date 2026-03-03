<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Attributes\On;
use Livewire\Component;
use Relaticle\Workflow\Forms\Actions\VariablePickerAction;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowNode;
use Relaticle\Workflow\Services\FieldResolverService;
use Relaticle\Workflow\WorkflowManager;

/**
 * @property Schema $form
 */
class WorkflowConfigPanel extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?string $workflowId = null;

    public ?string $selectedNodeId = null;

    public ?string $nodeType = null;

    public ?string $actionType = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    #[On('node-selected')]
    public function selectNode(string $nodeId, string $nodeType, ?string $actionType = null): void
    {
        $this->authorizeTenantAccess();

        $this->selectedNodeId = $nodeId;
        $this->nodeType = $nodeType;
        $this->actionType = $actionType;

        $node = WorkflowNode::where('workflow_id', $this->workflowId)
            ->where('node_id', $nodeId)
            ->first();

        $config = $node?->config ?? [];

        // Serialize any nested arrays/objects to JSON strings for textarea fields
        $formData = $this->prepareConfigForForm($config);

        $this->form->fill($formData);

        // Auto-detect cron preset from existing cron_expression
        if ($nodeType === 'trigger') {
            $presets = ['*/5 * * * *', '0 * * * *', '0 9 * * *', '0 9 * * 1', '0 9 1 * *'];
            $cronExpr = $config['cron_expression'] ?? null;
            if ($cronExpr && in_array($cronExpr, $presets, true)) {
                $this->data['cron_preset'] = $cronExpr;
            } elseif ($cronExpr) {
                $this->data['cron_preset'] = 'custom';
            }
        }
    }

    #[On('node-deselected')]
    public function deselectNode(): void
    {
        $this->selectedNodeId = null;
        $this->nodeType = null;
        $this->actionType = null;
        $this->data = [];
    }

    public function saveConfig(): void
    {
        $this->authorizeTenantAccess();

        $this->form->validate();

        $formData = $this->form->getState();

        // Deserialize JSON strings back to arrays for object fields
        $config = $this->prepareConfigForStorage($formData);

        WorkflowNode::where('workflow_id', $this->workflowId)
            ->where('node_id', $this->selectedNodeId)
            ->update(['config' => $config]);

        // Sync trigger entity_type to workflow.trigger_config
        if ($this->nodeType === 'trigger' && $this->workflowId) {
            $workflow = Workflow::find($this->workflowId);
            if ($workflow) {
                $triggerConfig = $workflow->trigger_config ?? [];
                if (isset($config['entity_type'])) {
                    $triggerConfig['entity_type'] = $config['entity_type'];
                }
                if (isset($config['event'])) {
                    $triggerConfig['event'] = $config['event'];
                }
                $workflow->update(['trigger_config' => $triggerConfig]);
            }
        }

        $this->dispatch('node-config-saved', nodeId: $this->selectedNodeId, config: $config);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    public function getPanelTitle(): string
    {
        if ($this->nodeType === 'trigger') {
            return 'Trigger Settings';
        }

        if ($this->nodeType === 'action' && $this->actionType) {
            $actionClass = $this->resolveActionClass();
            if ($actionClass) {
                return $actionClass::label() . ' Settings';
            }
        }

        if ($this->nodeType) {
            return ucfirst($this->nodeType) . ' Settings';
        }

        return 'Settings';
    }

    public function getActionLabel(): string
    {
        if ($this->nodeType === 'trigger') {
            return 'Trigger';
        }

        $actionClass = $this->resolveActionClass();

        return $actionClass ? $actionClass::label() : ucfirst($this->nodeType ?? 'Block');
    }

    public function getActionCategory(): string
    {
        if ($this->nodeType === 'trigger') {
            return 'Trigger';
        }

        $actionClass = $this->resolveActionClass();

        return $actionClass && method_exists($actionClass, 'category')
            ? $actionClass::category()
            : ucfirst($this->nodeType ?? 'General');
    }

    public function getActionIcon(): string
    {
        if ($this->nodeType === 'trigger') {
            return 'heroicon-o-bolt';
        }

        $actionClass = $this->resolveActionClass();

        return $actionClass && method_exists($actionClass, 'icon')
            ? $actionClass::icon()
            : 'heroicon-o-cube';
    }

    public function getCategoryColor(): string
    {
        return match ($this->nodeType) {
            'trigger' => 'green',
            'condition' => 'amber',
            'delay' => 'gray',
            'loop' => 'purple',
            'stop' => 'red',
            default => match ($this->getActionCategory()) {
                'Records' => 'sky',
                'Communication' => 'green',
                'Integration' => 'purple',
                'Flow Control' => 'orange',
                default => 'blue',
            },
        };
    }

    protected function resolveActionClass(): ?string
    {
        if (! $this->actionType) {
            return null;
        }

        $actions = app(WorkflowManager::class)->getActions();

        return $actions[$this->actionType] ?? null;
    }

    /**
     * Get the trigger's entity type for contextual placeholders.
     */
    protected function getTriggerEntityType(): ?string
    {
        if (! $this->workflowId) {
            return null;
        }

        $workflow = Workflow::find($this->workflowId);

        return $workflow?->trigger_config['entity_type'] ?? null;
    }

    /**
     * Get a singular label for an entity type.
     */
    protected function singularEntity(?string $entity): string
    {
        return match ($entity) {
            'people' => 'person',
            'companies' => 'company',
            'opportunities' => 'opportunity',
            'tasks' => 'task',
            'notes' => 'note',
            default => $entity ?? 'record',
        };
    }

    /**
     * Verify the workflow belongs to the current user's tenant.
     */
    protected function authorizeTenantAccess(): void
    {
        if (!$this->workflowId) {
            return;
        }

        $workflow = Workflow::find($this->workflowId);
        if (!$workflow) {
            abort(404);
        }

        $user = auth()->user();

        // Skip tenant check if no user or no tenant_id set
        if (!$user || !$workflow->tenant_id) {
            return;
        }

        $userTenantId = $user->current_team_id ?? $user->tenant_id ?? null;
        if ($userTenantId && $workflow->tenant_id !== $userTenantId) {
            abort(403);
        }
    }

    /**
     * Get the current workflow ID (used by action form field option resolvers).
     */
    public function getWorkflowId(): ?string
    {
        return $this->workflowId;
    }

    /**
     * Get the currently selected node ID (used by action form field option resolvers).
     */
    public function getNodeId(): ?string
    {
        return $this->selectedNodeId;
    }

    public function render(): \Illuminate\View\View
    {
        return view('workflow::livewire.config-panel');
    }

    protected function getFormSchema(): array
    {
        if (! $this->nodeType) {
            return [];
        }

        $descriptionField = Textarea::make('description')
            ->label('Description')
            ->placeholder('Add a description...')
            ->rows(2);

        return match ($this->nodeType) {
            'trigger' => [$descriptionField, ...$this->getTriggerFormSchema()],
            'action' => [$descriptionField, ...$this->getActionFormSchema()],
            'condition' => [$descriptionField, ...$this->getConditionFormSchema()],
            'delay' => [$descriptionField, ...$this->getDelayFormSchema()],
            'loop' => [$descriptionField, ...$this->getLoopFormSchema()],
            'stop' => [$descriptionField, ...$this->getStopFormSchema()],
            default => [$descriptionField],
        };
    }

    protected function getTriggerFormSchema(): array
    {
        return [
            Select::make('event')
                ->label('Trigger Event')
                ->options([
                    'record_created' => 'Record Created',
                    'record_updated' => 'Record Updated',
                    'record_deleted' => 'Record Deleted',
                    'manual' => 'Manual',
                    'webhook' => 'Webhook',
                    'scheduled' => 'Scheduled',
                ])
                ->live(),
            Select::make('entity_type')
                ->label('Record Type')
                ->options([
                    'people' => 'People',
                    'companies' => 'Companies',
                    'opportunities' => 'Opportunities',
                    'tasks' => 'Tasks',
                    'notes' => 'Notes',
                ])
                ->visible(fn (callable $get): bool => in_array($get('event'), ['record_created', 'record_updated', 'record_deleted']))
                ->helperText('Which type of record should trigger this workflow?'),
            TextInput::make('webhook_url')
                ->label('Webhook URL')
                ->disabled()
                ->visible(fn (callable $get): bool => $get('event') === 'webhook')
                ->default(fn () => $this->workflowId ? url("/workflow/api/workflows/{$this->workflowId}/webhook") : '')
                ->helperText('Send POST requests to this URL to trigger the workflow.')
                ->suffixAction(
                    Action::make('copy')
                        ->icon('heroicon-o-clipboard')
                        ->action(function ($state, $livewire) {
                            $livewire->js("navigator.clipboard.writeText(" . json_encode($state) . ")");
                        })
                ),
            Select::make('cron_preset')
                ->label('Schedule')
                ->options([
                    '*/5 * * * *' => 'Every 5 minutes',
                    '0 * * * *' => 'Every hour',
                    '0 9 * * *' => 'Every day at 9:00 AM',
                    '0 9 * * 1' => 'Every Monday at 9:00 AM',
                    '0 9 1 * *' => '1st of every month at 9:00 AM',
                    'custom' => 'Custom expression...',
                ])
                ->visible(fn (callable $get): bool => $get('event') === 'scheduled')
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state !== 'custom') {
                        $set('cron_expression', $state);
                    }
                }),
            TextInput::make('cron_expression')
                ->label('Cron Expression')
                ->placeholder('*/5 * * * *')
                ->visible(fn (callable $get): bool => $get('event') === 'scheduled' && $get('cron_preset') === 'custom')
                ->helperText('Standard cron syntax: minute hour day month weekday'),
        ];
    }

    protected function getActionFormSchema(): array
    {
        if (! $this->actionType) {
            return [];
        }

        $actions = app(WorkflowManager::class)->getActions();
        $actionClass = $actions[$this->actionType] ?? null;

        if (! $actionClass) {
            return [];
        }

        // Check if the action has a filamentForm method (added in Task 9)
        if (method_exists($actionClass, 'filamentForm')) {
            return $actionClass::filamentForm();
        }

        // Fall back to converting configSchema to basic Filament fields
        return $this->buildFormFromConfigSchema($actionClass::configSchema());
    }

    protected function getConditionFormSchema(): array
    {
        return [
            Select::make('match')
                ->label('Match')
                ->options([
                    'all' => 'All conditions (AND)',
                    'any' => 'Any condition (OR)',
                ])
                ->default('all'),
            Repeater::make('conditions')
                ->label('Conditions')
                ->schema([
                    Select::make('field')
                        ->label('Field')
                        ->searchable()
                        ->options(fn () => $this->getConditionFieldOptions())
                        ->placeholder('Select a field...')
                        ->columnSpan(1),
                    Select::make('operator')
                        ->label('Operator')
                        ->options(function (callable $get) {
                            $allOperators = [
                                'equals' => 'Equals',
                                'not_equals' => 'Not Equals',
                                'contains' => 'Contains',
                                'greater_than' => 'Greater Than',
                                'less_than' => 'Less Than',
                                'is_empty' => 'Is Empty',
                                'is_not_empty' => 'Is Not Empty',
                                'in' => 'In List',
                            ];

                            $field = $get('field');
                            if (! $field) {
                                return $allOperators;
                            }

                            // Attempt to resolve field type from upstream field metadata
                            $fieldType = $this->resolveFieldType($field);

                            if (! $fieldType) {
                                return $allOperators;
                            }

                            // Filter operators based on field type
                            return match ($fieldType) {
                                'integer', 'number', 'float', 'decimal' => array_intersect_key($allOperators, array_flip([
                                    'equals', 'not_equals', 'greater_than', 'less_than', 'is_empty', 'is_not_empty',
                                ])),
                                'boolean' => array_intersect_key($allOperators, array_flip([
                                    'equals', 'not_equals', 'is_empty', 'is_not_empty',
                                ])),
                                'array', 'object' => array_intersect_key($allOperators, array_flip([
                                    'contains', 'is_empty', 'is_not_empty', 'in',
                                ])),
                                default => $allOperators,
                            };
                        })
                        ->reactive()
                        ->columnSpan(1),
                    TextInput::make('value')
                        ->label('Value')
                        ->placeholder('Value or {{variable}}')
                        ->columnSpan(1)
                        ->suffixAction(
                            VariablePickerAction::make('pickConditionValue')
                                ->forField('value')
                        ),
                ])
                ->columns(3)
                ->defaultItems(1)
                ->addActionLabel('Add condition')
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => ($state['field'] ?? '') . ' ' . ($state['operator'] ?? '') . ' ' . ($state['value'] ?? '')),
        ];
    }

    protected function getDelayFormSchema(): array
    {
        return [
            TextInput::make('duration')
                ->label('Duration')
                ->numeric()
                ->minValue(0)
                ->placeholder('5'),
            Select::make('unit')
                ->label('Unit')
                ->options([
                    'minutes' => 'Minutes',
                    'hours' => 'Hours',
                    'days' => 'Days',
                ])
                ->placeholder('Select unit'),
        ];
    }

    protected function getLoopFormSchema(): array
    {
        return [
            Select::make('collection')
                ->label('Collection Path')
                ->searchable()
                ->options(fn () => $this->getCollectionOptions())
                ->placeholder('Select a collection...')
                ->helperText('Choose an array field from upstream steps to iterate over'),
        ];
    }

    protected function getStopFormSchema(): array
    {
        $entity = $this->getTriggerEntityType();
        $singular = $this->singularEntity($entity);
        $placeholder = $entity ? "Finished processing {$singular}" : 'Workflow complete';

        return [
            TextInput::make('reason')
                ->label('Reason')
                ->placeholder($placeholder),
        ];
    }

    /**
     * Get field options for condition node field selector.
     *
     * Resolves available fields from upstream workflow steps via the
     * FieldResolverService and formats them as Select options.
     *
     * @return array<string, string>
     */
    protected function getConditionFieldOptions(): array
    {
        if (! $this->workflowId || ! $this->selectedNodeId) {
            return [];
        }

        try {
            $service = app(FieldResolverService::class);
            $groups = $service->getAvailableFields($this->workflowId, $this->selectedNodeId);
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                // Strip {{ and }} since conditions use raw dot-notation paths
                $path = trim($field['fullPath'], '{}');
                $options[$path] = "[{$group['group']}] {$field['label']}";
            }
        }

        return $options;
    }

    /**
     * Get collection options for loop node collection selector.
     *
     * Resolves available fields from upstream workflow steps and filters
     * to only show array-typed fields that can be iterated over.
     *
     * @return array<string, string>
     */
    protected function getCollectionOptions(): array
    {
        if (! $this->workflowId || ! $this->selectedNodeId) {
            return [];
        }

        try {
            $service = app(FieldResolverService::class);
            $groups = $service->getAvailableFields($this->workflowId, $this->selectedNodeId);
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                // Only show fields that could be collections (array, object, mixed types)
                if (in_array($field['type'], ['array', 'object', 'mixed'], true)) {
                    $path = trim($field['fullPath'], '{}');
                    $options[$path] = "[{$group['group']}] {$field['label']}";
                }
            }
        }

        return $options;
    }

    /**
     * Resolve the type of a field by its path using the FieldResolverService.
     *
     * Looks up the field in the upstream field metadata and returns its type
     * (e.g., 'string', 'integer', 'boolean', etc.) or null if not found.
     */
    protected function resolveFieldType(string $fieldPath): ?string
    {
        if (! $this->workflowId || ! $this->selectedNodeId) {
            return null;
        }

        try {
            $service = app(FieldResolverService::class);
            $groups = $service->getAvailableFields($this->workflowId, $this->selectedNodeId);
        } catch (\Throwable) {
            return null;
        }

        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                $path = trim($field['fullPath'], '{}');
                if ($path === $fieldPath) {
                    return $field['type'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Convert a configSchema array into Filament form components.
     *
     * @param  array<string, array<string, mixed>>  $schema
     * @return array<\Filament\Forms\Components\Field>
     */
    protected function buildFormFromConfigSchema(array $schema): array
    {
        $fields = [];

        foreach ($schema as $key => $def) {
            $type = $def['type'] ?? 'string';
            $label = $def['label'] ?? $key;
            $required = $def['required'] ?? false;

            $field = match ($type) {
                'integer', 'number' => TextInput::make($key)->label($label)->numeric(),
                'boolean' => Toggle::make($key)->label($label),
                'object', 'array' => Textarea::make($key)
                    ->label($label)
                    ->rows(4)
                    ->placeholder('JSON'),
                'select' => Select::make($key)
                    ->label($label)
                    ->options($def['options'] ?? []),
                default => TextInput::make($key)->label($label),
            };

            if ($required) {
                $field = $field->required();
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Prepare config data for form display (serialize nested objects to JSON strings).
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function prepareConfigForForm(array $config): array
    {
        if (! $this->actionType) {
            return $config;
        }

        $actions = app(WorkflowManager::class)->getActions();
        $actionClass = $actions[$this->actionType] ?? null;

        if (! $actionClass || method_exists($actionClass, 'filamentForm')) {
            return $config;
        }

        $schema = $actionClass::configSchema();
        $prepared = $config;

        foreach ($schema as $key => $def) {
            $type = $def['type'] ?? 'string';
            if (in_array($type, ['object', 'array']) && isset($prepared[$key]) && is_array($prepared[$key])) {
                $prepared[$key] = json_encode($prepared[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }

        return $prepared;
    }

    /**
     * Prepare form data for storage (deserialize JSON strings back to arrays).
     *
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    protected function prepareConfigForStorage(array $formData): array
    {
        if (! $this->actionType) {
            return $formData;
        }

        $actions = app(WorkflowManager::class)->getActions();
        $actionClass = $actions[$this->actionType] ?? null;

        if (! $actionClass || method_exists($actionClass, 'filamentForm')) {
            return $formData;
        }

        $schema = $actionClass::configSchema();
        $prepared = $formData;

        foreach ($schema as $key => $def) {
            $type = $def['type'] ?? 'string';
            if (in_array($type, ['object', 'array']) && isset($prepared[$key]) && is_string($prepared[$key])) {
                $decoded = json_decode($prepared[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $prepared[$key] = $decoded;
                }
            }
        }

        return $prepared;
    }
}
