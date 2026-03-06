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
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Section;
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
            $presets = ['*/5 * * * *', '*/15 * * * *', '*/30 * * * *', '0 * * * *', '0 9 * * *', '0 9 * * 1-5', '0 9 * * 1', '0 9 1 * *'];
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
            return 'Trigger';
        }

        if ($this->nodeType === 'action' && $this->actionType) {
            $actionClass = $this->resolveActionClass();
            if ($actionClass) {
                return $actionClass::label();
            }
        }

        if ($this->nodeType) {
            return ucfirst($this->nodeType);
        }

        return 'Block';
    }

    public function getActionLabel(): string
    {
        return match ($this->nodeType) {
            'trigger' => 'Trigger',
            'filter' => 'Filter',
            'switch' => 'Switch',
            default => (function () {
                $actionClass = $this->resolveActionClass();
                return $actionClass ? $actionClass::label() : ucfirst($this->nodeType ?? 'Block');
            })(),
        };
    }

    public function getActionCategory(): string
    {
        return match ($this->nodeType) {
            'trigger' => 'Trigger',
            'filter' => 'Decisions',
            'switch' => 'Decisions',
            default => (function () {
                $actionClass = $this->resolveActionClass();
                return $actionClass && method_exists($actionClass, 'category')
                    ? $actionClass::category()
                    : ucfirst($this->nodeType ?? 'General');
            })(),
        };
    }

    public function getActionIcon(): string
    {
        return match ($this->nodeType) {
            'trigger' => 'heroicon-o-bolt',
            'filter' => 'heroicon-o-funnel',
            'switch' => 'heroicon-o-arrows-right-left',
            default => (function () {
                $actionClass = $this->resolveActionClass();
                return $actionClass && method_exists($actionClass, 'icon')
                    ? $actionClass::icon()
                    : 'heroicon-o-cube';
            })(),
        };
    }

    public function getCategoryColor(): string
    {
        return match ($this->nodeType) {
            'trigger' => 'purple',
            'condition' => 'amber',
            'filter' => 'amber',
            'switch' => 'purple',
            'delay' => 'gray',
            'loop' => 'purple',
            'stop' => 'red',
            default => match ($this->getActionCategory()) {
                'Records' => 'sky',
                'Communication' => 'green',
                'Integration' => 'purple',
                'Flow Control' => 'orange',
                default => 'purple',
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
            ->label('Notes')
            ->placeholder('Add a note about what this step does...')
            ->rows(2)
            ->helperText('Optional — helps you and your team understand the purpose of this step.');

        return match ($this->nodeType) {
            'trigger' => [$descriptionField, ...$this->getTriggerFormSchema()],
            'action' => [$descriptionField, ...$this->getActionFormSchema()],
            'condition' => [$descriptionField, ...$this->getConditionFormSchema()],
            'filter' => [$descriptionField, ...$this->getFilterFormSchema()],
            'switch' => [$descriptionField, ...$this->getSwitchFormSchema()],
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
                ->label('When does this start?')
                ->options([
                    'record_created' => 'A record is created',
                    'record_updated' => 'A record is updated',
                    'record_deleted' => 'A record is deleted',
                    'manual' => 'When I click Run',
                    'webhook' => 'Data arrives from outside',
                    'scheduled' => 'On a schedule',
                ])
                ->placeholder('Choose what starts this workflow...')
                ->helperText('Pick the event that should kick off this workflow.')
                ->live()
                ->columnSpanFull(),

            // Record event fields
            Select::make('entity_type')
                ->label('Which record type?')
                ->options([
                    'people' => 'People',
                    'companies' => 'Companies',
                    'opportunities' => 'Opportunities',
                    'tasks' => 'Tasks',
                    'notes' => 'Notes',
                ])
                ->placeholder('Choose a record type...')
                ->visible(fn (callable $get): bool => in_array($get('event'), ['record_created', 'record_updated', 'record_deleted']))
                ->helperText('Which type of record should trigger this workflow?'),

            // Webhook fields
            TextInput::make('webhook_url')
                ->label('Your webhook URL')
                ->disabled()
                ->visible(fn (callable $get): bool => $get('event') === 'webhook')
                ->default(fn () => $this->workflowId ? url("/workflow/api/workflows/{$this->workflowId}/webhook") : '')
                ->helperText('Share this URL with the app that should trigger this workflow.')
                ->columnSpanFull()
                ->suffixAction(
                    Action::make('copy')
                        ->icon('heroicon-o-clipboard')
                        ->action(function ($state, $livewire) {
                            $livewire->js("navigator.clipboard.writeText(" . json_encode($state) . ")");
                        })
                ),

            // Schedule fields
            Section::make('Schedule')
                ->schema([
                    Select::make('cron_preset')
                        ->label('How often?')
                        ->options([
                            '*/5 * * * *' => 'Every 5 minutes',
                            '*/15 * * * *' => 'Every 15 minutes',
                            '*/30 * * * *' => 'Every 30 minutes',
                            '0 * * * *' => 'Every hour',
                            '0 9 * * *' => 'Every day at 9:00 AM',
                            '0 9 * * 1-5' => 'Every weekday at 9:00 AM',
                            '0 9 * * 1' => 'Every Monday at 9:00 AM',
                            '0 9 1 * *' => '1st of every month at 9:00 AM',
                            'custom_interval' => 'Custom interval...',
                            'custom' => 'Custom cron expression...',
                        ])
                        ->placeholder('Pick a schedule...')
                        ->helperText('Choose how frequently this workflow should run automatically.')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state !== 'custom' && $state !== 'custom_interval') {
                                $set('cron_expression', $state);
                            }
                        }),
                    TextInput::make('custom_interval_value')
                        ->label('Run every')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->default(1)
                        ->placeholder('1')
                        ->visible(fn (callable $get): bool => $get('cron_preset') === 'custom_interval')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $unit = $get('custom_interval_unit') ?? 'hours';
                            $val = max(1, (int) ($state ?? 1));
                            $set('cron_expression', self::buildCronFromInterval($val, $unit, $get('custom_interval_time') ?? '09:00', $get('custom_interval_day') ?? '1'));
                        }),
                    Select::make('custom_interval_unit')
                        ->label('Time unit')
                        ->options([
                            'minutes' => 'Minutes',
                            'hours' => 'Hours',
                            'days' => 'Days',
                            'weeks' => 'Weeks',
                        ])
                        ->default('hours')
                        ->visible(fn (callable $get): bool => $get('cron_preset') === 'custom_interval')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $val = max(1, (int) ($get('custom_interval_value') ?? 1));
                            $set('cron_expression', self::buildCronFromInterval($val, $state ?? 'hours', $get('custom_interval_time') ?? '09:00', $get('custom_interval_day') ?? '1'));
                        }),
                    TextInput::make('custom_interval_time')
                        ->label('At what time?')
                        ->type('time')
                        ->default('09:00')
                        ->visible(fn (callable $get): bool => $get('cron_preset') === 'custom_interval' && in_array($get('custom_interval_unit'), ['days', 'weeks']))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $val = max(1, (int) ($get('custom_interval_value') ?? 1));
                            $unit = $get('custom_interval_unit') ?? 'days';
                            $set('cron_expression', self::buildCronFromInterval($val, $unit, $state ?? '09:00', $get('custom_interval_day') ?? '1'));
                        }),
                    Select::make('custom_interval_day')
                        ->label('On which day?')
                        ->options([
                            '1' => 'Monday',
                            '2' => 'Tuesday',
                            '3' => 'Wednesday',
                            '4' => 'Thursday',
                            '5' => 'Friday',
                            '6' => 'Saturday',
                            '0' => 'Sunday',
                        ])
                        ->default('1')
                        ->visible(fn (callable $get): bool => $get('cron_preset') === 'custom_interval' && $get('custom_interval_unit') === 'weeks')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $val = max(1, (int) ($get('custom_interval_value') ?? 1));
                            $set('cron_expression', self::buildCronFromInterval($val, 'weeks', $get('custom_interval_time') ?? '09:00', $state ?? '1'));
                        }),
                    TextInput::make('cron_expression')
                        ->label('Cron expression')
                        ->placeholder('*/5 * * * *')
                        ->visible(fn (callable $get): bool => $get('cron_preset') === 'custom')
                        ->helperText('Use cron syntax: minute hour day month weekday. Example: "0 9 * * 1-5" runs at 9 AM on weekdays.'),
                ])
                ->visible(fn (callable $get): bool => $get('event') === 'scheduled')
                ->columnSpanFull(),
        ];
    }

    protected static function buildCronFromInterval(int $value, string $unit, string $time = '09:00', string $day = '1'): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $time ?: '09:00'));

        return match ($unit) {
            'minutes' => "*/{$value} * * * *",
            'hours' => "0 */{$value} * * *",
            'days' => "{$minute} {$hour} */{$value} * *",
            'weeks' => "{$minute} {$hour} * * {$day}",
            default => "0 */{$value} * * *",
        };
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
                ->label('Continue if')
                ->options([
                    'all' => 'ALL of these are true',
                    'any' => 'ANY of these are true',
                ])
                ->default('all')
                ->helperText('Choose whether all conditions must match, or just one.'),
            Repeater::make('conditions')
                ->label('Conditions')
                ->schema([
                    Select::make('field')
                        ->label('Field')
                        ->searchable()
                        ->options(fn () => $this->getConditionFieldOptions())
                        ->placeholder('Choose a field...')
                        ->columnSpan(1),
                    Select::make('operator')
                        ->label('Comparison')
                        ->options(function (callable $get) {
                            $allOperators = [
                                'equals' => 'is',
                                'not_equals' => 'is not',
                                'contains' => 'includes',
                                'greater_than' => 'is more than',
                                'less_than' => 'is less than',
                                'is_empty' => 'is blank',
                                'is_not_empty' => 'has a value',
                                'in' => 'is one of',
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
                        ->placeholder('Enter a value...')
                        ->columnSpan(1)
                        ->suffixAction(
                            VariablePickerAction::make('pickConditionValue')
                                ->forField('value')
                        ),
                ])
                ->columns(3)
                ->defaultItems(1)
                ->addActionLabel('+ Add another condition')
                ->collapsible()
                ->itemLabel(function (array $state): ?string {
                    $field = $state['field'] ?? '';
                    $op = $state['operator'] ?? '';
                    $value = $state['value'] ?? '';
                    if (!$field) return null;
                    $fieldName = last(explode('.', $field));
                    $opLabel = match($op) {
                        'equals' => 'is',
                        'not_equals' => 'is not',
                        'contains' => 'includes',
                        'greater_than' => 'is more than',
                        'less_than' => 'is less than',
                        'is_empty' => 'is blank',
                        'is_not_empty' => 'has a value',
                        'in' => 'is one of',
                        default => $op,
                    };
                    return trim("{$fieldName} {$opLabel} {$value}");
                }),
        ];
    }

    protected function getFilterFormSchema(): array
    {
        return [
            Select::make('match')
                ->label('Continue only if')
                ->options([
                    'all' => 'ALL of these are true',
                    'any' => 'ANY of these are true',
                ])
                ->default('all')
                ->helperText('Records that don\'t match will be stopped — no "else" branch.'),
            Repeater::make('conditions')
                ->label('Conditions')
                ->schema([
                    Select::make('field')
                        ->label('Field')
                        ->searchable()
                        ->options(fn () => $this->getConditionFieldOptions())
                        ->placeholder('Choose a field...')
                        ->columnSpan(1),
                    Select::make('operator')
                        ->label('Comparison')
                        ->options(function (callable $get) {
                            $allOperators = [
                                'equals' => 'is',
                                'not_equals' => 'is not',
                                'contains' => 'includes',
                                'greater_than' => 'is more than',
                                'less_than' => 'is less than',
                                'is_empty' => 'is blank',
                                'is_not_empty' => 'has a value',
                                'in' => 'is one of',
                            ];

                            $field = $get('field');
                            if (! $field) {
                                return $allOperators;
                            }

                            $fieldType = $this->resolveFieldType($field);
                            if (! $fieldType) {
                                return $allOperators;
                            }

                            return match ($fieldType) {
                                'integer', 'number', 'float', 'decimal' => array_intersect_key($allOperators, array_flip([
                                    'equals', 'not_equals', 'greater_than', 'less_than', 'is_empty', 'is_not_empty',
                                ])),
                                'boolean' => array_intersect_key($allOperators, array_flip([
                                    'equals', 'not_equals', 'is_empty', 'is_not_empty',
                                ])),
                                default => $allOperators,
                            };
                        })
                        ->reactive()
                        ->columnSpan(1),
                    TextInput::make('value')
                        ->label('Value')
                        ->placeholder('Enter a value...')
                        ->columnSpan(1)
                        ->suffixAction(
                            VariablePickerAction::make('pickFilterValue')
                                ->forField('value')
                        ),
                ])
                ->columns(3)
                ->defaultItems(1)
                ->addActionLabel('+ Add another condition')
                ->collapsible()
                ->itemLabel(function (array $state): ?string {
                    $field = $state['field'] ?? '';
                    $op = $state['operator'] ?? '';
                    $value = $state['value'] ?? '';
                    if (!$field) return null;
                    $fieldName = last(explode('.', $field));
                    $opLabel = match($op) {
                        'equals' => 'is', 'not_equals' => 'is not', 'contains' => 'includes',
                        'greater_than' => 'is more than', 'less_than' => 'is less than',
                        'is_empty' => 'is blank', 'is_not_empty' => 'has a value', 'in' => 'is one of',
                        default => $op,
                    };
                    return trim("{$fieldName} {$opLabel} {$value}");
                }),
        ];
    }

    protected function getSwitchFormSchema(): array
    {
        return [
            Select::make('field')
                ->label('Switch based on')
                ->searchable()
                ->options(fn () => $this->getConditionFieldOptions())
                ->placeholder('Choose a field to branch on...')
                ->helperText('The workflow will take a different path for each value of this field.'),
            Repeater::make('cases')
                ->label('Branches')
                ->schema([
                    TextInput::make('value')
                        ->label('When value is')
                        ->placeholder('Enter a value...')
                        ->columnSpan(1),
                    TextInput::make('label')
                        ->label('Branch name')
                        ->placeholder('e.g. Hot, Warm, Cold...')
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->defaultItems(2)
                ->addActionLabel('+ Add branch')
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => ($state['label'] ?? '') ?: ($state['value'] ?? null)),
            Toggle::make('hasDefault')
                ->label('Include a "Default" branch')
                ->helperText('Catches any value not matched by the branches above.')
                ->default(true),
        ];
    }

    protected function getDelayFormSchema(): array
    {
        return [
            TextInput::make('duration')
                ->label('Wait for')
                ->numeric()
                ->minValue(0)
                ->placeholder('5')
                ->helperText('How long should the workflow pause before continuing?'),
            Select::make('unit')
                ->label('Time unit')
                ->options([
                    'minutes' => 'Minutes',
                    'hours' => 'Hours',
                    'days' => 'Days',
                ])
                ->placeholder('Choose a unit...'),
        ];
    }

    protected function getLoopFormSchema(): array
    {
        return [
            Select::make('collection')
                ->label('Loop through')
                ->searchable()
                ->options(fn () => $this->getCollectionOptions())
                ->placeholder('Choose a list to loop through...')
                ->helperText('Pick a list from an earlier step. The workflow will repeat for each item.'),
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
