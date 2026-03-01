# Relaticle-Native Workflow Engine Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Transform the generic workflow package into a Relaticle-native engine with Livewire config panel, rich run viewer, and Attio-level UX polish.

**Architecture:** Bottom-up approach — Phase 1 rewrites the engine to be Relaticle-aware (entity registry, variable resolver, CRUD actions). Phase 2 replaces the Alpine config panel with a Livewire component using Filament forms. Phase 3 overhauls the run viewer with canvas status overlays and block detail popovers. Phase 4 polishes the workflow list and settings.

**Tech Stack:** Laravel 11, Filament v3, Livewire v3, Alpine.js, AntV X6 graph library, Tailwind CSS 4

**Design Doc:** `docs/plans/2026-03-01-workflow-relaticle-native-design.md`

---

## Phase 1: Relaticle-Native Engine (Tasks 1-7)

### Task 1: Entity Registry — RelaticleSchema Service

Create a service that provides schema info for all 5 Relaticle entity types.

**Files:**
- Create: `packages/workflow/src/Schema/RelaticleSchema.php`
- Create: `packages/workflow/src/Schema/EntityDefinition.php`
- Create: `packages/workflow/src/Schema/FieldDefinition.php`
- Test: `packages/workflow/tests/Feature/Schema/RelaticleSchemaTest.php`

**What to build:**

`RelaticleSchema` is a singleton service (bound in the service provider) that returns metadata about Relaticle's entities.

`EntityDefinition` is a simple value object:
```php
class EntityDefinition {
    public function __construct(
        public string $key,          // 'people', 'companies', etc.
        public string $label,        // 'People', 'Companies', etc.
        public string $modelClass,   // App\Models\People::class
        public string $tableName,    // 'people'
    ) {}
}
```

`FieldDefinition` is a value object:
```php
class FieldDefinition {
    public function __construct(
        public string $key,          // 'name', 'email', or custom field code
        public string $label,        // 'Name', 'Email'
        public string $type,         // 'string', 'text', 'integer', 'boolean', 'date', 'select', etc.
        public bool $isCustomField,  // false for standard, true for custom
        public ?string $customFieldId, // ULID if custom field
        public array $options = [],  // For select-type fields: [{value, label}]
        public bool $required = false,
    ) {}
}
```

`RelaticleSchema` methods:
```php
class RelaticleSchema {
    // Returns all entity definitions
    public function getEntities(): array;

    // Returns entity by key
    public function getEntity(string $key): ?EntityDefinition;

    // Returns standard + custom fields for an entity type
    // Queries CustomField model filtered by entity_type + current tenant
    public function getFields(string $entityKey): array; // FieldDefinition[]

    // Returns relationship definitions (e.g., People->Company)
    public function getRelationships(string $entityKey): array;
}
```

The 5 entities with their standard fields:
- **people**: name (string), company_id (relation→companies)
- **companies**: name (string), address (text), country (string), phone (string)
- **opportunities**: name (string), company_id (relation→companies), contact_id (relation→people)
- **tasks**: title (string)
- **notes**: title (string)

Custom fields are loaded dynamically from the `custom_fields` table where `entity_type` matches the model's morph class (e.g., `App\Models\People`). Use `CustomField::forEntity($modelClass)->active()->get()` to query them.

**Tests:**
- `it_returns_all_five_entities` — verify getEntities() returns people, companies, opportunities, tasks, notes
- `it_returns_standard_fields_for_people` — verify getFields('people') includes 'name'
- `it_returns_custom_fields_for_entity` — create a CustomField fixture, verify it appears in getFields()
- `it_returns_field_options_for_select_type` — create a select CustomField with options, verify options are populated

**Commit:** `feat(workflow): add RelaticleSchema entity registry service`

---

### Task 2: Upgrade Variable Resolver

Upgrade the variable resolver to support structured paths, relationship traversal, and step output references.

**Files:**
- Modify: `packages/workflow/src/Engine/VariableResolver.php`
- Test: `packages/workflow/tests/Feature/Engine/VariableResolverTest.php`

**What to build:**

The current resolver replaces `{{variable}}` using simple dot-notation on a flat context. Upgrade to support:

1. **Trigger record fields**: `{{trigger.record.name}}` → resolves from `$context['trigger']['record']`
2. **Custom field values**: `{{trigger.record.custom.deal_value}}` → loads custom field value by code from the trigger record model
3. **Relationship traversal**: `{{trigger.record.company.name}}` → follows BelongsTo relationships
4. **Step outputs**: `{{steps.action_2.output.id}}` → from `$context['steps']['action_2']['output']`
5. **Built-in**: `{{now}}`, `{{today}}` (keep existing)

The context structure becomes:
```php
$context = [
    'trigger' => [
        'record' => $eloquentModel,  // The actual model instance
        'event' => 'created',
    ],
    'steps' => [
        'action-2' => ['output' => ['id' => '...', 'sent' => true]],
    ],
];
```

Key implementation detail: When resolving `trigger.record.{field}`, check if the record is an Eloquent model. If so:
- Standard attributes: `$record->{$field}`
- Custom fields (`custom.{code}`): `$record->getCustomFieldValue($customField)` where `$customField` is looked up by code
- Relations (`{relation}.{field}`): `$record->{$relation}->{$field}`

**Backward compatibility:** Keep supporting the old flat `{{record.name}}` format — if a variable doesn't match the new structured format, fall back to the old dot-notation lookup.

**Tests:**
- `it_resolves_trigger_record_standard_field` — `{{trigger.record.name}}` with a People model
- `it_resolves_trigger_record_custom_field` — `{{trigger.record.custom.deal_value}}` with a custom field
- `it_resolves_relationship_traversal` — `{{trigger.record.company.name}}` with People→Company
- `it_resolves_step_output` — `{{steps.action-2.output.sent}}` from context
- `it_falls_back_to_old_format` — `{{record.name}}` still works for backward compat
- `it_resolves_built_in_now` — `{{now}}` returns ISO timestamp

**Commit:** `feat(workflow): upgrade VariableResolver with structured paths and relationship traversal`

---

### Task 3: Create Record Actions (CRUD)

Add 4 new action classes that directly operate on Relaticle's Eloquent models.

**Files:**
- Create: `packages/workflow/src/Actions/CreateRecordAction.php`
- Create: `packages/workflow/src/Actions/UpdateRecordAction.php`
- Create: `packages/workflow/src/Actions/FindRecordAction.php`
- Create: `packages/workflow/src/Actions/DeleteRecordAction.php`
- Test: `packages/workflow/tests/Feature/Actions/CreateRecordActionTest.php`
- Test: `packages/workflow/tests/Feature/Actions/UpdateRecordActionTest.php`
- Test: `packages/workflow/tests/Feature/Actions/FindRecordActionTest.php`
- Test: `packages/workflow/tests/Feature/Actions/DeleteRecordActionTest.php`

**CreateRecordAction:**
```php
// Config: { entity_type: 'people', field_mappings: { name: 'John', company_id: '...' }, custom_field_mappings: { deal_value: '1000' } }
// Execute: Creates record via $model::create(), saves custom fields, returns ['id' => $record->id, 'record' => $record->toArray()]
```
- Resolves `entity_type` to model class via `RelaticleSchema::getEntity()`
- Sets `team_id` from workflow's `tenant_id`
- Sets `creator_id` from the workflow's `creator_id`
- Sets `creation_source` to `CreationSource::SYSTEM`
- Saves custom field mappings via `$record->saveCustomFields()`

**UpdateRecordAction:**
```php
// Config: { record_source: 'trigger'|'step', step_node_id: 'action-2', field_mappings: { name: 'Updated' }, custom_field_mappings: { ... } }
// Execute: Finds the record from context, updates fields, returns ['id' => $record->id, 'updated' => true]
```
- `record_source: 'trigger'` → uses `$context['trigger']['record']`
- `record_source: 'step'` → uses record from a previous FindRecord step output

**FindRecordAction:**
```php
// Config: { entity_type: 'people', conditions: [{ field: 'name', operator: 'equals', value: 'John' }], limit: 1 }
// Execute: Queries with conditions, returns ['found' => true, 'record' => $record->toArray(), 'id' => $record->id] or ['found' => false]
```
- Supports same operators as ConditionEvaluator: equals, not_equals, contains, greater_than, less_than, is_empty, is_not_empty
- Custom field conditions: if field starts with `custom.`, query via join on custom_field_values table

**DeleteRecordAction:**
```php
// Config: { record_source: 'trigger'|'step', step_node_id: 'action-2' }
// Execute: Soft-deletes the record, returns ['id' => $record->id, 'deleted' => true]
```

**Tests for each action:**
- Happy path: create/update/find/delete a People record
- Custom fields: create with custom field values, find by custom field
- Validation: missing required fields return error
- Tenant scoping: records get correct team_id

**Commit:** `feat(workflow): add CRUD record actions (Create, Update, Find, Delete)`

---

### Task 4: Remove Generic Registration, Wire Up Relaticle Actions

Replace the generic WorkflowManager registration with a hardcoded Relaticle-specific action registry.

**Files:**
- Modify: `packages/workflow/src/WorkflowManager.php`
- Modify: `packages/workflow/src/WorkflowServiceProvider.php`
- Modify: `app/Providers/WorkflowServiceProvider.php`
- Modify: `packages/workflow/config/workflow.php`

**What to change:**

1. **WorkflowManager**: Remove `registerTriggerableModel()` method. Remove `registerAction()` method. Replace with a hardcoded `getActions()` method that returns all action classes:
```php
public function getActions(): array {
    return [
        'send_email' => SendEmailAction::class,
        'send_webhook' => SendWebhookAction::class,
        'http_request' => HttpRequestAction::class,
        'create_record' => CreateRecordAction::class,
        'update_record' => UpdateRecordAction::class,
        'find_record' => FindRecordAction::class,
        'delete_record' => DeleteRecordAction::class,
    ];
}
```

2. **WorkflowManager**: Remove `getTriggerableModels()`. Add `getTriggerEntities()` that returns entities from `RelaticleSchema::getEntities()`.

3. **App WorkflowServiceProvider** (`app/Providers/WorkflowServiceProvider.php`): Remove all `registerTriggerableModel()` and `registerAction()` calls. The provider becomes minimal — just configures tenancy.

4. **Package WorkflowServiceProvider**: Bind `RelaticleSchema` as singleton. Register all actions in `getActions()`.

5. **Config**: Remove `triggerable_models` and `actions` config keys if they exist. Add `entities` key that lists the 5 entity types (or just hardcode in RelaticleSchema).

**Tests:**
- `it_returns_all_registered_actions` — verify getActions() returns all 7 actions
- `it_returns_trigger_entities` — verify getTriggerEntities() returns 5 entities

**Commit:** `refactor(workflow): replace generic registration with Relaticle-native action registry`

---

### Task 5: Update WorkflowExecutor for Structured Context

Modify the executor to build structured context and pass step outputs downstream.

**Files:**
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php`
- Test: `packages/workflow/tests/Feature/Engine/WorkflowExecutorTest.php` (update existing tests)

**What to change:**

1. **Context building** in `execute()`: Build the initial context as:
```php
$context = [
    'trigger' => [
        'record' => $triggerRecord, // Eloquent model if record_event trigger
        'event' => $event,
    ],
    'steps' => [],
    ...$additionalContext, // webhook payload, manual context, etc.
];
```

2. **Step output propagation**: After each action executes, add its output to context:
```php
// In executeActionNode(), after $output = $action->execute($resolvedConfig, $context):
$context['steps'][$node->node_id] = [
    'output' => $output,
    'status' => 'completed',
];
```

3. **Condition evaluation**: Update to use structured context for field resolution. `ConditionEvaluator::evaluate()` should be able to resolve `trigger.record.name` paths.

4. **Step recording**: The existing `createStep()` already records input_data and output_data — ensure $resolvedConfig is stored as input_data and $output as output_data.

5. **Max steps config**: Read from `$workflow->trigger_config['max_steps'] ?? config('workflow.max_steps_per_run', 100)` instead of hardcoded.

**Tests:**
- `it_propagates_step_outputs_in_context` — verify that a FindRecord output is available to a downstream UpdateRecord
- `it_builds_structured_trigger_context` — verify trigger.record is populated
- `it_reads_max_steps_from_workflow_config` — verify custom max_steps is respected

**Commit:** `feat(workflow): structured context with step output propagation in executor`

---

### Task 6: Variable Catalog API Endpoint

Add an API endpoint that returns available variables at a given point in the workflow graph.

**Files:**
- Create: `packages/workflow/src/Http/Controllers/VariableController.php`
- Modify: `packages/workflow/routes/api.php`
- Test: `packages/workflow/tests/Feature/Http/VariableControllerTest.php`

**What to build:**

`GET /workflow/api/workflows/{workflow}/variables?node_id={nodeId}`

Returns all variables available at the given node position in the graph:

```json
{
  "groups": [
    {
      "label": "Trigger Record (People)",
      "prefix": "trigger.record",
      "fields": [
        { "path": "trigger.record.name", "label": "Name", "type": "string" },
        { "path": "trigger.record.custom.deal_value", "label": "Deal Value", "type": "currency" }
      ]
    },
    {
      "label": "Step: Send Email (action-2)",
      "prefix": "steps.action-2.output",
      "fields": [
        { "path": "steps.action-2.output.sent", "label": "Sent", "type": "boolean" }
      ]
    },
    {
      "label": "Built-in",
      "prefix": "",
      "fields": [
        { "path": "now", "label": "Current Timestamp", "type": "datetime" },
        { "path": "today", "label": "Today's Date", "type": "date" }
      ]
    }
  ]
}
```

**Logic:**
1. Load the workflow's nodes and edges from DB
2. Use `GraphWalker` to find all nodes that are upstream (predecessors) of the given `node_id`
3. For the trigger node: include entity fields from `RelaticleSchema::getFields()` based on the workflow's trigger config entity type
4. For each upstream action node: include fields from that action's `outputSchema()`
5. Add built-in variables

**Tests:**
- `it_returns_trigger_record_fields` — verify trigger fields appear
- `it_returns_upstream_step_outputs` — verify predecessor step outputs appear
- `it_excludes_downstream_step_outputs` — verify unreachable step outputs don't appear
- `it_returns_built_in_variables` — verify now/today appear

**Commit:** `feat(workflow): add variable catalog API endpoint`

---

### Task 7: Update Action outputSchema and configSchema

Upgrade all existing actions to define proper `outputSchema()` and prepare for Filament form integration.

**Files:**
- Modify: `packages/workflow/src/Actions/SendEmailAction.php`
- Modify: `packages/workflow/src/Actions/SendWebhookAction.php`
- Modify: `packages/workflow/src/Actions/HttpRequestAction.php`
- Modify: `packages/workflow/src/Actions/DelayAction.php`
- Modify: `packages/workflow/src/Actions/CreateRecordAction.php` (from Task 3)
- Modify: `packages/workflow/src/Actions/UpdateRecordAction.php`
- Modify: `packages/workflow/src/Actions/FindRecordAction.php`
- Modify: `packages/workflow/src/Actions/DeleteRecordAction.php`

**What to change:**

Each action's `outputSchema()` should return descriptive metadata for the variable catalog:
```php
// SendEmailAction
public static function outputSchema(): array {
    return [
        'sent' => ['type' => 'boolean', 'label' => 'Email Sent'],
        'to' => ['type' => 'string', 'label' => 'Recipient'],
    ];
}

// CreateRecordAction
public static function outputSchema(): array {
    return [
        'id' => ['type' => 'string', 'label' => 'Record ID'],
        'created' => ['type' => 'boolean', 'label' => 'Was Created'],
    ];
}

// FindRecordAction
public static function outputSchema(): array {
    return [
        'found' => ['type' => 'boolean', 'label' => 'Record Found'],
        'id' => ['type' => 'string', 'label' => 'Record ID'],
        'record' => ['type' => 'object', 'label' => 'Found Record'],
    ];
}
```

Also add `category()` and `icon()` static methods to each action for the block picker:
```php
public static function category(): string { return 'Records'; }
public static function icon(): string { return 'heroicon-o-document-plus'; }
```

**Commit:** `feat(workflow): add output schemas, categories, and icons to all actions`

---

## Phase 2: Livewire Config Panel (Tasks 8-12)

### Task 8: Create WorkflowConfigPanel Livewire Component

Create the Livewire component that replaces the Alpine.js config panel.

**Files:**
- Create: `packages/workflow/src/Livewire/WorkflowConfigPanel.php`
- Create: `packages/workflow/resources/views/livewire/config-panel.blade.php`
- Modify: `packages/workflow/src/WorkflowServiceProvider.php` (register Livewire component)
- Modify: `packages/workflow/resources/views/builder.blade.php` (embed component)

**What to build:**

`WorkflowConfigPanel` is a Livewire component that:

1. Listens for `node-selected` event from Alpine (via `#[On('node-selected')]`)
2. Loads the `WorkflowNode` from DB by `workflow_id` + `node_id`
3. Dynamically builds a Filament form based on the node's type and action_type
4. Renders the form in the sidebar
5. On form submit, saves config back to `WorkflowNode.config`
6. Dispatches `node-updated` event back to Alpine with the new config

**Component properties:**
```php
public ?string $workflowId = null;
public ?string $selectedNodeId = null;
public ?string $nodeType = null;
public ?string $actionType = null;
public ?array $config = [];
```

**Key methods:**
```php
#[On('node-selected')]
public function selectNode(string $nodeId, string $nodeType, ?string $actionType = null): void
{
    $this->selectedNodeId = $nodeId;
    $this->nodeType = $nodeType;
    $this->actionType = $actionType;

    $node = WorkflowNode::where('workflow_id', $this->workflowId)
        ->where('node_id', $nodeId)->first();
    $this->config = $node?->config ?? [];

    $this->form->fill($this->config);
}

public function saveConfig(): void
{
    $data = $this->form->getState();
    WorkflowNode::where('workflow_id', $this->workflowId)
        ->where('node_id', $this->selectedNodeId)
        ->update(['config' => $data]);

    $this->dispatch('node-updated', nodeId: $this->selectedNodeId, config: $data);
}

protected function getFormSchema(): array
{
    if (!$this->actionType) return $this->getTriggerFormSchema();

    $actionClass = app(WorkflowManager::class)->getActions()[$this->actionType] ?? null;
    return $actionClass ? $actionClass::filamentForm() : [];
}
```

**Blade template** (`config-panel.blade.php`):
```blade
<div x-show="selectedNode" class="wf-panel wf-panel-open">
    <div class="wf-panel-header">
        <h3>{{ $this->getPanelTitle() }}</h3>
        <button wire:click="closePanel" class="wf-close-btn">&times;</button>
    </div>
    <div class="wf-panel-content">
        <form wire:submit="saveConfig">
            {{ $this->form }}
            <button type="submit" class="wf-btn wf-btn-primary mt-4">Save</button>
        </form>
    </div>
</div>
```

**Integration with builder.blade.php:**
Replace the Alpine config panel `<div x-show="panelView === 'config'">` section with:
```blade
@livewire('workflow-config-panel', ['workflowId' => $workflowId])
```

Add Alpine→Livewire bridge in the Alpine component:
```js
// When node is clicked on canvas:
this.$dispatch('node-selected', { nodeId, nodeType, actionType });

// Listen for updates from Livewire:
window.addEventListener('node-updated', (e) => {
    // Update canvas node display
});
```

**Commit:** `feat(workflow): add Livewire WorkflowConfigPanel component`

---

### Task 9: Add Filament Form Schemas to Actions

Add a static `filamentForm()` method to each action that returns Filament form components.

**Files:**
- Modify: `packages/workflow/src/Actions/Contracts/WorkflowAction.php` (add interface method)
- Modify all action classes to implement `filamentForm()`
- Create: `packages/workflow/src/Forms/Components/EntityFieldSelect.php` (custom Filament component)

**What to build:**

Add to the `WorkflowAction` interface:
```php
public static function filamentForm(): array; // Returns Filament form components
```

**Example — SendEmailAction::filamentForm():**
```php
public static function filamentForm(): array {
    return [
        TextInput::make('to')
            ->label('To')
            ->required()
            ->placeholder('recipient@example.com')
            ->suffixAction(VariablePickerAction::make()),
        TextInput::make('subject')
            ->label('Subject')
            ->required()
            ->suffixAction(VariablePickerAction::make()),
        Textarea::make('body')
            ->label('Body')
            ->rows(5)
            ->suffixAction(VariablePickerAction::make()),
    ];
}
```

**Example — CreateRecordAction::filamentForm():**
```php
public static function filamentForm(): array {
    return [
        Select::make('entity_type')
            ->label('Entity Type')
            ->options(fn () => collect(app(RelaticleSchema::class)->getEntities())
                ->mapWithKeys(fn ($e) => [$e->key => $e->label]))
            ->required()
            ->reactive(),
        // Dynamic field mappings based on selected entity_type
        Repeater::make('field_mappings')
            ->label('Field Values')
            ->schema(fn (Get $get) => [
                Select::make('field')
                    ->options(fn () => self::getFieldOptions($get('../../entity_type'))),
                TextInput::make('value')
                    ->suffixAction(VariablePickerAction::make()),
            ]),
    ];
}
```

**Example — FindRecordAction::filamentForm():**
```php
public static function filamentForm(): array {
    return [
        Select::make('entity_type')
            ->options(/* entity options */)
            ->required()
            ->reactive(),
        Repeater::make('conditions')
            ->label('Conditions')
            ->schema(fn (Get $get) => [
                Select::make('field')
                    ->options(fn () => self::getFieldOptions($get('../../entity_type'))),
                Select::make('operator')
                    ->options(['equals' => 'Equals', 'not_equals' => 'Not Equals', 'contains' => 'Contains', ...]),
                TextInput::make('value')
                    ->suffixAction(VariablePickerAction::make()),
            ]),
        TextInput::make('limit')->numeric()->default(1),
    ];
}
```

**Trigger form schema** (in WorkflowConfigPanel, not an action):
```php
protected function getTriggerFormSchema(): array {
    return [
        TextInput::make('description')->placeholder('Add a description...'),
        Select::make('trigger_event')
            ->options(['manual' => 'Manual', 'record_event' => 'Record Event', 'webhook' => 'Webhook']),
        Select::make('entity_type')
            ->visible(fn (Get $get) => $get('trigger_event') === 'record_event')
            ->options(/* entity options */),
        Select::make('record_event')
            ->visible(fn (Get $get) => $get('trigger_event') === 'record_event')
            ->options(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted']),
    ];
}
```

**Commit:** `feat(workflow): add Filament form schemas to all action types`

---

### Task 10: Variable Picker Filament Action

Create a reusable Filament Action for inserting variables into form fields.

**Files:**
- Create: `packages/workflow/src/Forms/Actions/VariablePickerAction.php`
- Create: `packages/workflow/resources/views/forms/variable-picker.blade.php`

**What to build:**

`VariablePickerAction` is a Filament `Action` that opens a modal when the `{x}` button is clicked.

The modal fetches available variables from the Variable Catalog API (Task 6) and displays them grouped by source (Trigger Record, Step outputs, Built-in).

When a variable is selected, it inserts `{{variable.path}}` into the parent input field.

```php
class VariablePickerAction extends Action {
    protected function setUp(): void {
        parent::setUp();

        $this->icon('heroicon-o-code-bracket')
            ->tooltip('Insert variable')
            ->modalHeading('Insert Variable')
            ->modalContent(fn () => view('workflow::forms.variable-picker', [
                'groups' => $this->getVariableGroups(),
            ]))
            ->action(function (array $data, $component) {
                // The selected variable path is passed back
                // JS handles inserting it into the input
            });
    }

    protected function getVariableGroups(): array {
        // Call the variable catalog for the current node
        $workflowId = $this->getLivewire()->workflowId;
        $nodeId = $this->getLivewire()->selectedNodeId;
        // Use VariableController logic to get groups
    }
}
```

The modal blade view shows grouped variables with click-to-select behavior:
```blade
<div class="space-y-4">
    @foreach($groups as $group)
        <div>
            <h4 class="text-sm font-semibold text-gray-500">{{ $group['label'] }}</h4>
            <div class="space-y-1 mt-1">
                @foreach($group['fields'] as $field)
                    <button type="button"
                        wire:click="insertVariable('{{ $field['path'] }}')"
                        class="w-full text-left px-3 py-1.5 text-sm rounded hover:bg-gray-100">
                        <span class="font-medium">{{ $field['label'] }}</span>
                        <span class="text-gray-400 text-xs ml-2">{{ $field['type'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
```

**Commit:** `feat(workflow): add variable picker Filament action with modal`

---

### Task 11: Alpine↔Livewire Event Bridge

Wire up the event communication between Alpine.js (canvas) and Livewire (config panel).

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/config-panel.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**What to change:**

1. **Remove Alpine config-panel.js**: The config form rendering is now handled by Livewire. Remove the Alpine mixin that generates HTML forms. Keep only the panel open/close state management.

2. **index.js — node:click handler**: Instead of dispatching `wf:node-selected` to Alpine, dispatch to Livewire:
```js
graph.on('node:click', ({ node }) => {
    const data = node.getData();
    // Dispatch to Livewire component
    Livewire.dispatch('node-selected', {
        nodeId: data.nodeId || node.id,
        nodeType: data.type,
        actionType: data.actionType || null,
    });
    // Also update Alpine state for panel visibility
    this.panelView = 'config';
    this.selectedNode = data;
});
```

3. **Listen for Livewire→Alpine updates**: In builder.blade.php, add:
```blade
<div x-on:node-updated.window="updateNodeOnCanvas($event.detail)">
```
Where `updateNodeOnCanvas()` finds the X6 node and updates its label/description.

4. **Panel management**: Alpine still controls which panel is visible (config/runs/settings). Livewire just renders the form content when `panelView === 'config'`.

**Commit:** `feat(workflow): wire Alpine↔Livewire event bridge for config panel`

---

### Task 12: Block Type Header with Category + Change Button

Add rich block type header to the config panel and a "Change" button.

**Files:**
- Modify: `packages/workflow/src/Livewire/WorkflowConfigPanel.php`
- Modify: `packages/workflow/resources/views/livewire/config-panel.blade.php`

**What to build:**

The config panel header shows:
- Block icon (from action's `icon()` method)
- Category pill (from action's `category()` method) — colored badge
- Block name (from action's `label()` method)
- "Change" button that opens a modal with the block picker to swap action type

```blade
<div class="flex items-center gap-3 mb-4">
    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
        <x-dynamic-component :component="$this->getActionIcon()" class="w-4 h-4 text-blue-600" />
    </div>
    <div>
        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
            {{ $this->getActionCategory() }}
        </span>
        <h3 class="text-sm font-semibold">{{ $this->getActionLabel() }}</h3>
    </div>
    <button wire:click="openChangeBlockModal" class="ml-auto text-xs text-blue-500 hover:underline">
        Change
    </button>
</div>
```

The "Change" button opens a Filament modal listing all available actions. Selecting one changes the node's `action_type` and resets the config form.

**Commit:** `feat(workflow): add block type header with icon, category, and Change button`

---

## Phase 3: Run Viewer Overhaul (Tasks 13-17)

### Task 13: Run List Sidebar with Sequential Numbers

Upgrade the run list to show "Run #N" with colored dots and summary header.

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/run-history.js`
- Modify: `packages/workflow/resources/views/builder.blade.php` (runs panel section)
- Modify: `packages/workflow/src/Http/Controllers/RunController.php` (add run number)

**What to change:**

1. **RunController**: Modify the list endpoint to return runs with a sequential number. Add `row_number` using a DB query or simply number them by position (newest = highest):
```php
$runs = $workflow->runs()
    ->orderByDesc('created_at')
    ->limit(50)
    ->get()
    ->map(function ($run, $index) use ($total) {
        return [
            ...$run->toArray(),
            'number' => $total - $index, // Sequential run number
        ];
    });
```
Also add `$total = $workflow->runs()->count();` for the summary header.

2. **run-history.js**: Update the Alpine component to display:
- Summary header: "Completed 12" or "Running 1" badge
- Each entry: "Run #12" with colored status dot, relative timestamp ("3h ago"), duration

3. **builder.blade.php**: Update the runs panel HTML to use Tailwind:
```html
<div class="flex items-center gap-2 mb-3">
    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">
        Completed <span x-text="completedCount"></span>
    </span>
</div>
<template x-for="run in runs" :key="run.id">
    <button @click="selectRun(run)" class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full" :class="run.status === 'completed' ? 'bg-green-500' : 'bg-red-500'"></span>
        <span class="text-sm font-medium" x-text="'Run #' + run.number"></span>
        <span class="text-xs text-gray-400 ml-auto" x-text="timeAgo(run.started_at)"></span>
    </button>
</template>
```

**Commit:** `feat(workflow): upgrade run list with sequential numbers and status summary`

---

### Task 14: Canvas Status Badge Overlays

Add status pill badges on canvas nodes when viewing a run.

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/BaseNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/index.js` (enterRunView method)

**What to change:**

1. **BaseNode.js**: Add a status badge slot to the node HTML template. When a node has `_runStatus` data, render a badge:
```js
const statusBadge = data._runStatus
    ? `<span class="absolute -top-2 -right-2 px-2 py-0.5 text-[10px] font-semibold rounded-full shadow-sm
        ${data._runStatus === 'completed' ? 'bg-green-100 text-green-700' : ''}
        ${data._runStatus === 'failed' ? 'bg-red-100 text-red-700' : ''}
        ${data._runStatus === 'skipped' ? 'bg-gray-100 text-gray-500' : ''}
        ${data._runStatus === 'running' ? 'bg-blue-100 text-blue-700 animate-pulse' : ''}
    ">${statusLabel}</span>`
    : '';
```

2. **index.js enterRunView()**: When loading run step data onto nodes, set `_runStatus` on each node's data and call `node.updateData()` to trigger re-render:
```js
enterRunView(run) {
    // ... existing logic ...
    steps.forEach(step => {
        const node = graph.getCellById(step.node_id);
        if (node) {
            node.setData({ ...node.getData(), _runStatus: step.status }, { overwrite: true });
        }
    });
}
```

3. **exitRunView()**: Clear `_runStatus` from all nodes when leaving run view.

**Commit:** `feat(workflow): add status badge overlays on canvas nodes in run view`

---

### Task 15: Edge Path Coloring in Run View

Color edges green/red/gray based on run execution path.

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/index.js` (enterRunView)

**What to change:**

In `enterRunView()`, after setting node statuses, color the edges:

```js
// Color edges based on step statuses
graph.getEdges().forEach(edge => {
    const sourceNode = edge.getSourceNode();
    const targetNode = edge.getTargetNode();
    if (!sourceNode || !targetNode) return;

    const sourceStatus = sourceNode.getData()?._runStatus;
    const targetStatus = targetNode.getData()?._runStatus;

    if (sourceStatus === 'completed' && targetStatus === 'completed') {
        edge.attr('line/stroke', '#22c55e'); // green
        edge.attr('line/strokeWidth', 2.5);
    } else if (sourceStatus === 'completed' && targetStatus === 'failed') {
        edge.attr('line/stroke', '#ef4444'); // red
        edge.attr('line/strokeWidth', 2.5);
    } else {
        edge.attr('line/stroke', '#d1d5db'); // gray — not traversed
        edge.attr('line/strokeWidth', 1.5);
        edge.attr('line/strokeDasharray', '4 2'); // dashed for untraversed
    }
});
```

In `exitRunView()`, reset all edges to default styling.

**Commit:** `feat(workflow): color edges green/red/gray based on run execution path`

---

### Task 16: Block Detail Popover in Run View

Add a dark tooltip popover showing step details when clicking a node during run view.

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php` (add popover HTML)
- Modify: `packages/workflow/resources/js/workflow-builder/index.js` (show popover on node click)
- Modify: `packages/workflow/resources/css/workflow-builder.css` (popover styles)

**What to build:**

An Alpine.js-driven popover that appears near the clicked node. Data comes from the already-loaded run steps.

**Alpine state** (in index.js):
```js
stepPopover: null, // { visible, x, y, step }
```

**Node click handler in run view mode**:
```js
graph.on('node:click', ({ node, e }) => {
    if (!this._runViewMode) return;

    const nodeId = node.id;
    const step = this._currentRunSteps.find(s => s.node_id === nodeId);
    if (!step) return;

    const pos = graph.localToPage(node.getBBox().center());
    this.stepPopover = {
        visible: true,
        x: pos.x + 20,
        y: pos.y - 100,
        step: step,
    };
});
```

**Popover HTML** (in builder.blade.php):
```html
<div x-show="stepPopover?.visible" x-cloak
     :style="`position:fixed; left:${stepPopover?.x}px; top:${stepPopover?.y}px; z-index:200;`"
     @click.outside="stepPopover = null"
     class="w-80 bg-gray-900 text-white rounded-xl shadow-2xl p-4 text-sm">

    <!-- Status badge -->
    <div class="flex items-center gap-2 mb-3">
        <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
              :class="stepPopover?.step?.status === 'completed' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'"
              x-text="stepPopover?.step?.status"></span>
        <span class="text-gray-400 text-xs ml-auto" x-text="formatDuration(stepPopover?.step?.started_at, stepPopover?.step?.completed_at)"></span>
    </div>

    <!-- Timestamps -->
    <div class="space-y-1 text-xs text-gray-400 mb-3">
        <div>Started: <span class="text-gray-200" x-text="formatTime(stepPopover?.step?.started_at)"></span></div>
        <div>Completed: <span class="text-gray-200" x-text="formatTime(stepPopover?.step?.completed_at)"></span></div>
    </div>

    <!-- Inputs -->
    <div x-show="stepPopover?.step?.input_data" class="mb-3">
        <h5 class="text-xs font-semibold text-gray-400 uppercase mb-1">Inputs</h5>
        <pre class="text-xs bg-gray-800 rounded p-2 max-h-32 overflow-auto"
             x-text="JSON.stringify(stepPopover?.step?.input_data, null, 2)"></pre>
    </div>

    <!-- Outputs -->
    <div x-show="stepPopover?.step?.output_data" class="mb-3">
        <h5 class="text-xs font-semibold text-gray-400 uppercase mb-1">Outputs</h5>
        <pre class="text-xs bg-gray-800 rounded p-2 max-h-32 overflow-auto"
             x-text="JSON.stringify(stepPopover?.step?.output_data, null, 2)"></pre>
    </div>

    <!-- Error -->
    <div x-show="stepPopover?.step?.error_message" class="text-red-400 text-xs">
        <h5 class="font-semibold uppercase mb-1">Error</h5>
        <p x-text="stepPopover?.step?.error_message"></p>
    </div>
</div>
```

**Commit:** `feat(workflow): add block detail popover in run view`

---

### Task 17: Run View Step Data Loading

Ensure the run detail API returns step data with node_id mapping and the frontend loads it properly.

**Files:**
- Modify: `packages/workflow/src/Http/Controllers/RunController.php`
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/run-history.js`

**What to change:**

1. **RunController show()**: Ensure the step response includes the `node_id` (from WorkflowNode) for canvas mapping:
```php
$run->load(['steps.node']);
$steps = $run->steps->map(function ($step) {
    return [
        'id' => $step->id,
        'node_id' => $step->node->node_id, // The canvas node ID like "action-2"
        'status' => $step->status->value,
        'input_data' => $step->input_data,
        'output_data' => $step->output_data,
        'error_message' => $step->error_message,
        'started_at' => $step->started_at?->toISOString(),
        'completed_at' => $step->completed_at?->toISOString(),
    ];
});
```

2. **run-history.js**: When a run is selected, store the steps for the popover:
```js
async selectRun(run) {
    const response = await fetch(`/workflow/api/workflow-runs/${run.id}`);
    const data = await response.json();
    this._currentRunSteps = data.run.steps;

    // Dispatch to canvas to enter run view
    window.dispatchEvent(new CustomEvent('wf:enter-run-view', {
        detail: { steps: data.run.steps }
    }));
}
```

**Commit:** `feat(workflow): load and map run step data for canvas overlays`

---

## Phase 4: Workflow List & Settings Polish (Tasks 18-23)

### Task 18: Workflow List — Two-Line Row Layout with Creator

Upgrade the Filament table to show name+description in one column and add Created By.

**Files:**
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`

**What to change:**

1. **Composite name+description column**: Replace separate name and description columns with a single column using a custom view:
```php
TextColumn::make('name')
    ->description(fn (Workflow $record): string => $record->description ?? 'No description')
    ->searchable()
    ->sortable(),
```

2. **Created By column**:
```php
TextColumn::make('creator.name')
    ->label('Created By')
    ->sortable()
    ->toggleable(),
```

3. **Formatted run count**: (requires withCount)
```php
TextColumn::make('runs_count')
    ->counts('runs')
    ->label('Runs')
    ->formatStateUsing(fn ($state) => number_format($state) . ' runs')
    ->sortable(),
```

4. **Enhanced grouping**: Add more group options:
```php
->groups([
    'status',
    'trigger_type',
    Group::make('creator.name')->label('Created By'),
])
```

**Commit:** `feat(workflow): upgrade workflow list with two-line rows, creator, run counts`

---

### Task 19: Workflow Favorites

Add a star/favorite toggle per workflow.

**Files:**
- Create: `packages/workflow/database/migrations/2026_03_01_000002_create_workflow_favorites_table.php`
- Create: `packages/workflow/src/Models/WorkflowFavorite.php`
- Modify: `packages/workflow/src/Models/Workflow.php` (add relationship)
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php` (add star toggle)

**What to build:**

1. **Migration**: `workflow_favorites` table with `id`, `user_id` (FK→users), `workflow_id` (FK→workflows), `created_at`. Unique index on (user_id, workflow_id).

2. **Model**: Simple pivot model.

3. **Workflow relationship**:
```php
public function favorites(): HasMany { return $this->hasMany(WorkflowFavorite::class); }
public function isFavoritedBy(User $user): bool {
    return $this->favorites()->where('user_id', $user->id)->exists();
}
```

4. **Filament table**: Add a ToggleColumn or an Action column for the star:
```php
Tables\Columns\IconColumn::make('is_favorited')
    ->label('')
    ->icon(fn (Workflow $record) => $record->isFavoritedBy(auth()->user()) ? 'heroicon-s-star' : 'heroicon-o-star')
    ->color(fn (Workflow $record) => $record->isFavoritedBy(auth()->user()) ? 'warning' : 'gray')
    ->action(fn (Workflow $record) => $record->toggleFavorite(auth()->user()))
    ->width('40px'),
```

**Commit:** `feat(workflow): add workflow favorites with star toggle`

---

### Task 20: Settings Tab — Execution Limits

Add "Maximum steps per run" setting to the workflow settings panel.

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php` (settings panel)
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/top-bar.js` (save settings)
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php` (read setting)

**What to change:**

1. **Settings panel HTML**: Add a number input for max steps:
```html
<div class="wf-config-group">
    <label class="text-xs font-semibold text-gray-500 uppercase">Execution Limits</label>
    <div class="flex items-center gap-2 mt-2">
        <label class="text-sm">Maximum steps per run</label>
        <input type="number" x-model="maxStepsPerRun" min="1" max="1000"
               class="w-20 text-sm border rounded px-2 py-1">
    </div>
    <p class="text-xs text-gray-400 mt-1">Workflow run will stop if this limit is reached</p>
</div>
```

2. **top-bar.js**: Add `maxStepsPerRun` state, load from `trigger_config.max_steps` on canvas load, save to workflow config.

3. **WorkflowExecutor**: Read max steps from workflow config:
```php
$maxSteps = $workflow->trigger_config['max_steps'] ?? config('workflow.max_steps_per_run', 100);
```

**Commit:** `feat(workflow): add configurable max steps per run in settings`

---

### Task 21: Settings Tab — Failure Notifications

Add "Notify on failure" toggle to settings.

**Files:**
- Create: `packages/workflow/src/Notifications/WorkflowRunFailedNotification.php`
- Modify: `packages/workflow/resources/views/builder.blade.php` (settings panel)
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/top-bar.js`
- Modify: `packages/workflow/src/Engine/WorkflowExecutor.php` (send notification)

**What to build:**

1. **Notification class**: A Laravel notification sent to the workflow creator when a run fails:
```php
class WorkflowRunFailedNotification extends Notification {
    public function __construct(public Workflow $workflow, public WorkflowRun $run) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array {
        return [
            'title' => "Workflow '{$this->workflow->name}' failed",
            'body' => $this->run->error_message ?? 'An error occurred during execution.',
            'workflow_id' => $this->workflow->id,
            'run_id' => $this->run->id,
        ];
    }
}
```

2. **Settings toggle**: In the settings panel:
```html
<div class="flex items-center justify-between mt-4">
    <div>
        <label class="text-sm font-medium">Notify on failure</label>
        <p class="text-xs text-gray-400">Receive a notification when this workflow fails</p>
    </div>
    <button type="button" @click="notifyOnFailure = !notifyOnFailure"
            :class="notifyOnFailure ? 'bg-blue-500' : 'bg-gray-300'"
            class="relative w-10 h-5 rounded-full transition-colors">
        <span :class="notifyOnFailure ? 'translate-x-5' : 'translate-x-0.5'"
              class="block w-4 h-4 bg-white rounded-full transition-transform shadow"></span>
    </button>
</div>
```

3. **Executor**: In `failRun()`, check if notifications are enabled:
```php
if ($workflow->trigger_config['notify_on_failure'] ?? false) {
    $workflow->creator?->notify(new WorkflowRunFailedNotification($workflow, $run));
}
```

**Commit:** `feat(workflow): add failure notification toggle in settings`

---

### Task 22: Run Count Badge on Runs Tab

Show run count badge on the "Runs" tab in the builder top bar.

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/run-history.js`

**What to change:**

1. **Run tab label**: Change from static "Runs" to dynamic:
```html
<button ...>Runs <span x-show="totalRuns > 0"
    class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold bg-gray-200 text-gray-600 rounded-full"
    x-text="totalRuns"></span></button>
```

2. **run-history.js**: Store `totalRuns` from the API response and expose it to the parent Alpine component:
```js
loadRuns() {
    // ... fetch ...
    this.totalRuns = data.total;
}
```

3. **RunController**: Return total count in the list response:
```php
return response()->json([
    'runs' => $runs,
    'total' => $workflow->runs()->count(),
]);
```

**Commit:** `feat(workflow): add run count badge on Runs tab`

---

### Task 23: Final Build, E2E Verification, and Cleanup

Build all assets and verify everything works end-to-end in the browser.

**Steps:**
1. Build workflow assets: `cd packages/workflow && npm run build`
2. Copy to public: `cp resources/dist/* ../../public/vendor/workflow/`
3. Build main app CSS: `npx vite build` (for Tailwind class compilation)
4. Browser test: Login → Workflows list → verify two-line rows, creator, favorites
5. Browser test: Open builder → verify Livewire config panel loads on node click
6. Browser test: Click Runs tab → verify numbered run list with status summary
7. Browser test: Select a run → verify canvas status badges and edge coloring
8. Browser test: Click a node in run view → verify detail popover
9. Browser test: Click Settings tab → verify max steps and notification toggle
10. Browser test: Create new workflow → verify block picker and action config forms

**Commit:** `feat(workflow): final build and asset deployment`

---

## Summary

| Phase | Tasks | Description |
|-------|-------|-------------|
| 1 | 1-7 | Engine: Entity registry, variable resolver, CRUD actions, action registry |
| 2 | 8-12 | Livewire config panel with Filament forms, variable picker, event bridge |
| 3 | 13-17 | Run viewer: numbered list, canvas badges, edge coloring, detail popover |
| 4 | 18-23 | Polish: list layout, favorites, settings, run count badge, E2E verification |

**Total:** 23 tasks across 4 phases.

**Execution approach:** Use `superpowers:executing-plans` in a separate session with git worktree isolation.
