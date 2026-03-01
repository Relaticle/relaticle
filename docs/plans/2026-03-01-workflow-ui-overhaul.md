# Workflow UI/UX Overhaul — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rebuild the workflow builder UI to match Attio-style patterns — full-width canvas, collapsible right config panel, `+` button block picker, variable picker, workflow lifecycle states, and run visualization.

**Architecture:** Alpine.js components for interactive panels (config, variable picker, block picker, run history). AntV X6 retained for the canvas graph. Blade template restructured to new layout. Backend adds lifecycle status enum, outputSchema on actions, and run list API.

**Tech Stack:** Alpine.js (via Filament), AntV X6 v2, vanilla CSS, Laravel/Filament, Pest tests

---

## Task 1: Backend — Workflow Lifecycle Status

Add `status` enum replacing `is_active` boolean.

**Files:**
- Create: `packages/workflow/src/Enums/WorkflowStatus.php`
- Create: `packages/workflow/database/migrations/2026_03_01_000001_add_status_to_workflows_table.php`
- Modify: `packages/workflow/src/Models/Workflow.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`
- Modify: `packages/workflow/src/Observers/WorkflowModelObserver.php`
- Modify: `packages/workflow/src/Triggers/RecordEventTrigger.php`
- Modify: `packages/workflow/src/Jobs/EvaluateScheduledWorkflowsJob.php`
- Test: `packages/workflow/tests/Feature/WorkflowLifecycleTest.php`

**Step 1: Create WorkflowStatus enum**

```php
// packages/workflow/src/Enums/WorkflowStatus.php
<?php
declare(strict_types=1);

namespace Relaticle\Workflow\Enums;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Paused = 'paused';
    case Archived = 'archived';

    public function canTrigger(): bool
    {
        return $this === self::Live;
    }
}
```

**Step 2: Create migration**

```php
// Migration: add_status_to_workflows_table
Schema::table($prefix . 'workflows', function (Blueprint $table) {
    $table->string('status')->default('draft')->after('is_active');
    $table->timestamp('published_at')->nullable()->after('status');
});

// Data migration: convert is_active to status
DB::table($prefix . 'workflows')
    ->where('is_active', true)
    ->update(['status' => 'live', 'published_at' => DB::raw('updated_at')]);
DB::table($prefix . 'workflows')
    ->where('is_active', false)
    ->update(['status' => 'draft']);

// Drop is_active
Schema::table($prefix . 'workflows', function (Blueprint $table) {
    $table->dropColumn('is_active');
});
```

**Step 3: Update Workflow model**

- Replace `'is_active'` in `$fillable` with `'status'`, `'published_at'`
- Replace `'is_active' => 'boolean'` cast with `'status' => WorkflowStatus::class`, `'published_at' => 'datetime'`
- Add accessor: `public function getIsActiveAttribute(): bool { return $this->status === WorkflowStatus::Live; }`
- Update `canActivate()` — no change needed (checks nodes, not status)

**Step 4: Update all places that check `is_active`**

In `WorkflowModelObserver`, `RecordEventTrigger`, `EvaluateScheduledWorkflowsJob`, `ManualTrigger`, `WebhookTrigger`:
- Replace `->where('is_active', true)` with `->where('status', WorkflowStatus::Live)`
- Replace `$workflow->is_active` checks with `$workflow->status->canTrigger()`

**Step 5: Update WorkflowResource form and table**

- Replace `Toggle::make('is_active')` with `Select::make('status')` using WorkflowStatus options
- Replace `IconColumn::make('is_active')->boolean()` with `TextColumn::make('status')->badge()->color(fn (WorkflowStatus $state) => match ($state) { ... })`

**Step 6: Write tests**

```php
// packages/workflow/tests/Feature/WorkflowLifecycleTest.php
it('creates workflows in draft status by default', function () { ... });
it('only triggers workflows in live status', function () { ... });
it('publishes a draft workflow to live', function () { ... });
it('pauses a live workflow', function () { ... });
it('archives a workflow', function () { ... });
it('restores archived workflow to paused', function () { ... });
it('preserves backward compat via is_active accessor', function () { ... });
```

**Step 7: Run tests, commit**

```bash
cd packages/workflow && php vendor/bin/pest --testsuite=Feature
git add -A && git commit -m "feat(workflow): add lifecycle status enum replacing is_active"
```

---

## Task 2: Backend — Output Schema on Actions

Add `outputSchema()` to `WorkflowAction` interface so the variable picker knows what each block produces.

**Files:**
- Modify: `packages/workflow/src/Actions/Contracts/WorkflowAction.php`
- Modify: `packages/workflow/src/Actions/BaseAction.php`
- Modify: `packages/workflow/src/Actions/SendEmailAction.php`
- Modify: `packages/workflow/src/Actions/SendWebhookAction.php`
- Modify: `packages/workflow/src/Actions/HttpRequestAction.php`
- Modify: `packages/workflow/src/Actions/DelayAction.php`
- Modify: `packages/workflow/src/Actions/LoopAction.php`
- Modify: `packages/workflow/src/Http/Controllers/CanvasController.php`
- Test: `packages/workflow/tests/Feature/OutputSchemaTest.php`

**Step 1: Add `outputSchema()` to interface**

```php
// In WorkflowAction interface, add:
/** @return array<string, array{type: string, label: string}> */
public static function outputSchema(): array;
```

**Step 2: Add default in BaseAction**

```php
public static function outputSchema(): array
{
    return [];
}
```

**Step 3: Add outputSchema to each action**

```php
// SendEmailAction
public static function outputSchema(): array
{
    return [
        'sent' => ['type' => 'boolean', 'label' => 'Was Sent'],
        'to' => ['type' => 'string', 'label' => 'Recipient'],
    ];
}

// HttpRequestAction
public static function outputSchema(): array
{
    return [
        'status_code' => ['type' => 'number', 'label' => 'Status Code'],
        'success' => ['type' => 'boolean', 'label' => 'Was Successful'],
        'response_body' => ['type' => 'string', 'label' => 'Response Body'],
    ];
}

// SendWebhookAction — same as HttpRequestAction

// DelayAction
public static function outputSchema(): array
{
    return [
        'delayed' => ['type' => 'boolean', 'label' => 'Was Delayed'],
        'delay_seconds' => ['type' => 'number', 'label' => 'Delay Seconds'],
    ];
}

// LoopAction
public static function outputSchema(): array
{
    return [
        'item_count' => ['type' => 'number', 'label' => 'Item Count'],
    ];
}
```

**Step 4: Include outputSchema in Canvas API response**

In `CanvasController::show()`, the `meta.registered_actions` already includes `configSchema`. Add `outputSchema`:

```php
$registeredActions[$key] = [
    'label' => $class::label(),
    'configSchema' => $class::configSchema(),
    'outputSchema' => $class::outputSchema(), // ADD THIS
];
```

**Step 5: Also add trigger output schemas to the meta response**

Add a new `meta.trigger_outputs` key describing what each trigger type produces:

```php
'trigger_outputs' => [
    'record_created' => [
        'record' => ['type' => 'object', 'label' => 'Created Record'],
        'event' => ['type' => 'string', 'label' => 'Event Name'],
    ],
    'record_updated' => [
        'record' => ['type' => 'object', 'label' => 'Updated Record'],
        'event' => ['type' => 'string', 'label' => 'Event Name'],
    ],
    // ... etc for each trigger type
],
```

**Step 6: Write tests, run, commit**

```bash
cd packages/workflow && php vendor/bin/pest --testsuite=Feature
git commit -m "feat(workflow): add outputSchema to WorkflowAction interface"
```

---

## Task 3: Backend — Run History API

Add API endpoints for listing runs and viewing run details with step inputs/outputs.

**Files:**
- Modify: `packages/workflow/routes/api.php`
- Modify: `packages/workflow/src/Http/Controllers/CanvasController.php` (or create RunController)
- Test: `packages/workflow/tests/Feature/RunApiTest.php`

**Step 1: Add routes**

```php
// In routes/api.php, add:
Route::get('workflows/{workflow}/runs', [RunController::class, 'index']);
Route::get('workflow-runs/{run}', [RunController::class, 'show']);
```

**Step 2: Create RunController**

```php
// packages/workflow/src/Http/Controllers/RunController.php
class RunController
{
    public function index(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);
        $runs = $workflow->runs()
            ->orderByDesc('started_at')
            ->limit(50)
            ->get(['id', 'status', 'started_at', 'completed_at', 'error_message']);

        return response()->json(['runs' => $runs]);
    }

    public function show(string $runId): JsonResponse
    {
        $run = WorkflowRun::with(['steps.node'])->findOrFail($runId);

        return response()->json([
            'run' => [
                'id' => $run->id,
                'status' => $run->status,
                'started_at' => $run->started_at,
                'completed_at' => $run->completed_at,
                'error_message' => $run->error_message,
                'context_data' => $run->context_data,
                'steps' => $run->steps->map(fn ($step) => [
                    'id' => $step->id,
                    'node_id' => $step->node?->node_id,
                    'status' => $step->status,
                    'input_data' => $step->input_data,
                    'output_data' => $step->output_data,
                    'error_message' => $step->error_message,
                    'started_at' => $step->started_at,
                    'completed_at' => $step->completed_at,
                ]),
            ],
        ]);
    }
}
```

**Step 3: Write tests, run, commit**

```bash
cd packages/workflow && php vendor/bin/pest --testsuite=Feature
git commit -m "feat(workflow): add run history API endpoints"
```

---

## Task 4: Backend — Publish/Pause/Archive Actions

Add Filament actions and API endpoint for lifecycle transitions.

**Files:**
- Modify: `packages/workflow/src/Http/Controllers/CanvasController.php`
- Modify: `packages/workflow/routes/api.php`
- Test: `packages/workflow/tests/Feature/WorkflowLifecycleTest.php` (extend)

**Step 1: Add lifecycle API endpoint**

```php
// routes/api.php
Route::post('workflows/{workflow}/publish', [WorkflowLifecycleController::class, 'publish']);
Route::post('workflows/{workflow}/pause', [WorkflowLifecycleController::class, 'pause']);
Route::post('workflows/{workflow}/archive', [WorkflowLifecycleController::class, 'archive']);
Route::post('workflows/{workflow}/restore', [WorkflowLifecycleController::class, 'restore']);
```

**Step 2: Create WorkflowLifecycleController**

```php
class WorkflowLifecycleController
{
    public function publish(string $workflowId): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);

        // Validate all blocks are complete
        $errors = $workflow->getActivationErrors();
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $workflow->update([
            'status' => WorkflowStatus::Live,
            'published_at' => now(),
        ]);

        return response()->json(['status' => 'live']);
    }

    public function pause(string $workflowId): JsonResponse { ... }
    public function archive(string $workflowId): JsonResponse { ... }
    public function restore(string $workflowId): JsonResponse { ... }
}
```

**Step 3: Write tests, run, commit**

```bash
cd packages/workflow && php vendor/bin/pest --testsuite=Feature
git commit -m "feat(workflow): add lifecycle transition API endpoints"
```

---

## Task 5: Frontend — New Blade Layout

Rewrite the builder blade template to the new Attio-style layout.

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Rewrite blade template**

Replace entire file with new layout:
- Top bar: back link, editable name, runs/settings/publish buttons
- Main area: canvas (flex-1) + right panel (320px, togglable)
- Bottom toolbar: pointer/drag mode, zoom, organize
- Remove old sidebar
- Add Alpine.js `x-data` for state management on the root element
- Wire up Alpine components for config panel, block picker, run history

Key Alpine state on root:

```blade
<div x-data="workflowBuilder(@js($workflowId))" ...>
```

This Alpine component manages:
- `selectedNode` — currently selected node data
- `panelView` — 'settings' | 'config' | 'runs'
- `isRunView` — whether viewing a run (read-only mode)
- `workflowStatus` — current lifecycle status

**Step 2: Build and verify layout renders**

```bash
cd packages/workflow && npm run build
```

**Step 3: Commit**

```bash
git commit -m "feat(workflow): rewrite builder blade to Attio-style layout"
```

---

## Task 6: Frontend — Redesigned Node Shapes

Rewrite all X6 node shapes with new visual design (wider, colored headers, config summaries, + button).

**Files:**
- Create: `packages/workflow/resources/js/workflow-builder/nodes/BaseNode.js`
- Modify: All node files in `nodes/` directory (TriggerNode.js, ActionNode.js, ConditionNode.js, DelayNode.js, LoopNode.js, StopNode.js)

**Step 1: Create BaseNode with shared rendering logic**

```javascript
// BaseNode.js — shared HTML renderer
export function createNodeHTML(data, options) {
    const { color, icon, label, summary, showAddButton = true } = options;
    return `
        <div class="wf-block" style="--block-color: ${color}">
            <div class="wf-block-header">
                <span class="wf-block-icon">${icon}</span>
                <span class="wf-block-label">${label}</span>
            </div>
            <div class="wf-block-body">
                <span class="wf-block-summary">${summary || 'Click to configure'}</span>
            </div>
        </div>
    `;
}
```

**Step 2: Update each node shape**

Each node uses `createNodeHTML()` with its category color, icon, and config summary logic. Width increased to 240px. Height varies: 72px standard, 80px for condition.

**Step 3: Build and test visually**

```bash
cd packages/workflow && npm run build
```

**Step 4: Commit**

```bash
git commit -m "feat(workflow): redesign node shapes with category colors and summaries"
```

---

## Task 7: Frontend — Config Panel (Alpine.js)

Replace the vanilla JS config panel with an Alpine.js component in the right panel.

**Files:**
- Create: `packages/workflow/resources/js/workflow-builder/alpine/config-panel.js`
- Remove logic from: `packages/workflow/resources/js/workflow-builder/config-panel.js` (keep as thin bridge)
- Modify: `packages/workflow/resources/views/builder.blade.php` (right panel markup)

**Step 1: Create Alpine config panel component**

The Alpine component (`configPanel`) manages:
- `nodeData` — current node's data object
- `registeredActions` — cached action metadata from API
- `updateNodeData(key, value)` — updates node data and syncs to X6

The Blade template renders the panel with Alpine directives:
- `x-show="selectedNode"` on the panel
- `x-text`, `x-model` bindings for form fields
- Template switching based on `nodeData.type`

**Step 2: Wire Alpine to X6 graph events**

In index.js, dispatch custom events that Alpine listens to:

```javascript
graph.on('node:click', ({ node }) => {
    window.dispatchEvent(new CustomEvent('wf:node-selected', {
        detail: { nodeId: node.id, data: node.getData() }
    }));
});

graph.on('blank:click', () => {
    window.dispatchEvent(new CustomEvent('wf:node-deselected'));
});
```

Alpine component listens:

```javascript
init() {
    this.$el.addEventListener('wf:node-selected', (e) => {
        this.nodeData = e.detail.data;
        this.selectedNodeId = e.detail.nodeId;
    });
}
```

**Step 3: Build, test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): Alpine.js config panel replacing vanilla JS"
```

---

## Task 8: Frontend — Block Picker (+ Button)

Replace the drag-and-drop sidebar with inline `+` buttons that open a categorized block picker popover.

**Files:**
- Create: `packages/workflow/resources/js/workflow-builder/alpine/block-picker.js`
- Remove: `packages/workflow/resources/js/workflow-builder/sidebar.js`
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`

**Step 1: Create block picker Alpine component**

The picker is a floating popover that appears when:
1. User clicks `+` button rendered on the canvas (as an X6 tool or overlay)
2. Shows categorized list of available block types with search filter
3. Selecting a block type adds it to the graph and auto-connects edges

**Step 2: Implement the `+` button as X6 overlay**

After each node's output port, render a small `+` circle. On click, position the popover near that node and open it. When a block type is selected:
1. Create new node below the clicked node
2. Add edge from clicked node to new node
3. Close popover

**Step 3: Implement search and categories**

```javascript
Alpine.data('blockPicker', () => ({
    open: false,
    search: '',
    sourceNodeId: null,
    position: { x: 0, y: 0 },
    categories: [
        { name: 'Triggers', blocks: [{ type: 'trigger', label: 'Trigger', icon: '⚡' }] },
        { name: 'Actions', blocks: [
            { type: 'action', label: 'Send Email', actionType: 'send_email', icon: '✉' },
            { type: 'action', label: 'Send Webhook', actionType: 'send_webhook', icon: '🔗' },
            { type: 'action', label: 'HTTP Request', actionType: 'http_request', icon: '🌐' },
        ]},
        { name: 'Logic', blocks: [
            { type: 'condition', label: 'If/Else', icon: '◇' },
        ]},
        { name: 'Timing', blocks: [
            { type: 'delay', label: 'Delay', icon: '⏱' },
        ]},
        { name: 'Flow', blocks: [
            { type: 'loop', label: 'Loop', icon: '↻' },
            { type: 'stop', label: 'Stop', icon: '■' },
        ]},
    ],
    get filteredCategories() {
        if (!this.search) return this.categories;
        const q = this.search.toLowerCase();
        return this.categories.map(cat => ({
            ...cat,
            blocks: cat.blocks.filter(b => b.label.toLowerCase().includes(q)),
        })).filter(cat => cat.blocks.length > 0);
    },
    selectBlock(block) { ... },
}));
```

**Step 4: Build, test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): block picker popover replacing sidebar drag-drop"
```

---

## Task 9: Frontend — Variable Picker

Create the `{x}` variable picker that shows available variables from upstream blocks.

**Files:**
- Create: `packages/workflow/resources/js/workflow-builder/alpine/variable-picker.js`
- Modify: Config panel to include `{x}` buttons on input fields

**Step 1: Build variable resolution logic**

The picker needs to:
1. Walk the graph backward from the current node to find all upstream blocks
2. For each upstream block, look up its `outputSchema` from `meta.registered_actions`
3. For trigger blocks, use `meta.trigger_outputs`
4. Group variables by source block with labels
5. Include built-in variables (`{{now}}`, `{{today}}`)

```javascript
function getUpstreamVariables(graph, nodeId, meta) {
    const variables = [];
    const visited = new Set();
    const queue = [nodeId];
    // BFS backward through incoming edges
    while (queue.length > 0) {
        const currentId = queue.shift();
        if (visited.has(currentId)) continue;
        visited.add(currentId);
        const incomingEdges = graph.getIncomingEdges(graph.getCellById(currentId));
        for (const edge of incomingEdges || []) {
            const sourceNode = edge.getSourceNode();
            if (sourceNode) {
                const data = sourceNode.getData();
                const outputs = getOutputsForNode(data, meta);
                variables.push({ source: data.label || data.type, outputs });
                queue.push(sourceNode.id);
            }
        }
    }
    // Add built-ins
    variables.push({
        source: 'Built-in',
        outputs: [
            { key: 'now', type: 'string', label: 'Current Timestamp' },
            { key: 'today', type: 'string', label: 'Today\'s Date' },
        ]
    });
    return variables;
}
```

**Step 2: Render picker as dropdown popover**

When `{x}` button clicked, show grouped variable list. Clicking a variable inserts `{{source.key}}` into the input field.

**Step 3: Build, test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): variable picker with upstream block resolution"
```

---

## Task 10: Frontend — Top Bar and Lifecycle Controls

Create the top bar with workflow name editing, publish button, and lifecycle controls.

**Files:**
- Create: `packages/workflow/resources/js/workflow-builder/alpine/top-bar.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Create top bar Alpine component**

```javascript
Alpine.data('topBar', (workflowId, initialStatus, initialName) => ({
    workflowId,
    status: initialStatus,
    name: initialName,
    editingName: false,
    saving: false,

    async publish() {
        this.saving = true;
        const res = await fetch(`/workflow/api/workflows/${this.workflowId}/publish`, { method: 'POST' });
        if (res.ok) {
            this.status = 'live';
            showToast('Workflow published', 'success');
        } else {
            const data = await res.json();
            showToast(data.errors?.join(', ') || 'Publish failed', 'error');
        }
        this.saving = false;
    },

    async pause() { ... },
    async archive() { ... },

    get publishLabel() {
        return this.status === 'live' ? 'Publish Changes' : 'Publish';
    },

    get statusColor() {
        return { draft: 'gray', live: 'green', paused: 'yellow', archived: 'red' }[this.status];
    },
}));
```

**Step 2: Wire into blade template header area**

**Step 3: Build, test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): top bar with lifecycle controls and inline name editing"
```

---

## Task 11: Frontend — Run History Panel

Create the run history slide-over panel and run visualization mode.

**Files:**
- Create: `packages/workflow/resources/js/workflow-builder/alpine/run-history.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Create run history Alpine component**

```javascript
Alpine.data('runHistory', (workflowId) => ({
    open: false,
    runs: [],
    selectedRun: null,
    runViewActive: false,

    async loadRuns() {
        const res = await fetch(`/workflow/api/workflows/${workflowId}/runs`);
        const data = await res.json();
        this.runs = data.runs;
    },

    async selectRun(runId) {
        const res = await fetch(`/workflow/api/workflow-runs/${runId}`);
        const data = await res.json();
        this.selectedRun = data.run;
        this.runViewActive = true;
        // Dispatch event for canvas to enter run view mode
        window.dispatchEvent(new CustomEvent('wf:enter-run-view', { detail: data.run }));
    },

    exitRunView() {
        this.runViewActive = false;
        this.selectedRun = null;
        window.dispatchEvent(new CustomEvent('wf:exit-run-view'));
    },
}));
```

**Step 2: Implement run view mode on canvas**

When entering run view:
- Canvas becomes read-only (disable editing, selection, keyboard shortcuts)
- Each node gets a status badge overlay based on matching step status
- Failed nodes get red border
- Skipped nodes get grey opacity
- Clicking a node in run view shows step details (inputs/outputs) in right panel

**Step 3: Build, test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): run history panel with canvas run visualization"
```

---

## Task 12: Frontend — Bottom Toolbar

Create the new bottom toolbar with pointer/drag mode, zoom, and auto-organize.

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/toolbar.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Rewrite toolbar**

- Pointer mode (V) / Drag mode (H) toggle — switches X6 `panning` config
- Zoom in/out/reset buttons
- Organize button — uses X6 dagre layout plugin
- Move toolbar from top to bottom of canvas

**Step 2: Add dagre auto-layout**

```javascript
import { DagreLayout } from '@antv/layout';

function organizeBlocks(graph) {
    const layout = new DagreLayout({ type: 'dagre', rankdir: 'TB', nodesep: 60, ranksep: 80 });
    const model = layout.layout({ nodes: [...], edges: [...] });
    // Apply new positions with animation
    model.nodes.forEach(n => {
        const cell = graph.getCellById(n.id);
        if (cell) cell.position(n.x, n.y, { transition: { duration: 300 } });
    });
}
```

**Step 3: Check if `@antv/layout` is already a dependency, install if needed**

```bash
cd packages/workflow && npm ls @antv/layout || npm install @antv/layout
```

**Step 4: Build, test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): bottom toolbar with pointer/drag mode and auto-organize"
```

---

## Task 13: Frontend — Complete CSS Rewrite

Rewrite the entire CSS to match the new Attio-style layout.

**Files:**
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Complete CSS rewrite**

Sections to cover:
1. **Layout**: Top bar (48px), canvas (flex-1), right panel (320px, animated slide), bottom toolbar (40px)
2. **Block styles**: `.wf-block` with `--block-color` custom property, header/body/summary, selected/error/run states
3. **Right panel**: `.wf-panel` with sections, form inputs, variable chips
4. **Block picker**: `.wf-picker` popover with categories, search, hover states
5. **Variable picker**: `.wf-var-picker` dropdown with grouped items
6. **Run history**: `.wf-runs` slide-over panel with status dots
7. **Bottom toolbar**: `.wf-toolbar-bottom` with icon buttons and mode toggle
8. **Connection lines**: X6 edge styling overrides
9. **Animations**: panel slide, block insert, toast notifications
10. **Dark mode**: Filament dark mode support via `.dark` class

**Step 2: Build, test visually, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): complete CSS rewrite for Attio-style builder"
```

---

## Task 14: Frontend — Wire Everything Together

Connect all Alpine components, X6 graph, and API calls into a working builder.

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/js/workflow-builder/graph.js`

**Step 1: Refactor index.js entry point**

1. Register all Alpine components globally
2. Initialize X6 graph
3. Set up bidirectional event bridge between Alpine and X6
4. Load canvas data and populate both X6 graph and Alpine state
5. Wire save functionality to collect data from both X6 and Alpine

**Step 2: Update graph.js**

- Add event dispatchers for Alpine integration
- Update keyboard shortcuts (add V/H mode toggle, Ctrl+S for save)
- Configure different modes (edit mode vs run view mode)

**Step 3: Build, full integration test, commit**

```bash
cd packages/workflow && npm run build
git commit -m "feat(workflow): wire Alpine components with X6 graph"
```

---

## Task 15: Update Filament Resource Pages

Update the WorkflowResource table, form, and page actions for the new lifecycle.

**Files:**
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ListWorkflows.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ViewWorkflow.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/EditWorkflow.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/WorkflowBuilder.php`

**Step 1: Update table columns**

- Replace `is_active` boolean column with `status` badge column
- Add color mapping: draft=gray, live=success, paused=warning, archived=danger
- Add status filter

**Step 2: Update form**

- Replace `is_active` toggle with `status` select
- Status is read-only on create (always draft)

**Step 3: Add lifecycle actions to ViewWorkflow**

```php
Actions\Action::make('publish')
    ->visible(fn ($record) => $record->status !== WorkflowStatus::Live)
    ->action(fn ($record) => $record->update(['status' => WorkflowStatus::Live, 'published_at' => now()])),
Actions\Action::make('pause')
    ->visible(fn ($record) => $record->status === WorkflowStatus::Live)
    ->action(fn ($record) => $record->update(['status' => WorkflowStatus::Paused])),
```

**Step 4: Update WorkflowBuilder page**

Pass `status` and `name` to the blade template for the top bar:

```php
public function mount(string $record): void
{
    $this->workflowId = $record;
    $workflow = Workflow::findOrFail($record);
    $this->workflowStatus = $workflow->status->value;
    $this->workflowName = $workflow->name;
}
```

**Step 5: Run tests, commit**

```bash
cd packages/workflow && php vendor/bin/pest --testsuite=Feature
git commit -m "feat(workflow): update Filament resources for lifecycle status"
```

---

## Task 16: Final Integration Test & Build

End-to-end verification of the complete UI overhaul.

**Files:**
- All modified files

**Step 1: Run all backend tests**

```bash
cd packages/workflow && php vendor/bin/pest --testsuite=Feature
```

Expected: All tests pass (existing + new lifecycle + output schema + run API tests)

**Step 2: Build frontend assets**

```bash
cd packages/workflow && npm run build
```

Expected: Both `workflow-builder.js` and `workflow-builder.css` built without errors

**Step 3: Verify assets exist**

```bash
ls -la ../../public/vendor/workflow/
```

Expected: Both files present with reasonable sizes

**Step 4: Manual smoke test checklist**

- [ ] Builder page loads without console errors
- [ ] Canvas renders with grid
- [ ] Existing workflows load nodes and edges correctly
- [ ] Clicking a node opens right config panel
- [ ] Clicking canvas background closes config panel
- [ ] `+` button appears and opens block picker
- [ ] Selecting a block from picker adds it to canvas
- [ ] Config panel shows correct fields per node type
- [ ] Variable picker shows upstream variables
- [ ] Publish button changes workflow status
- [ ] Status badge updates in list view
- [ ] Run history panel opens and lists runs
- [ ] Bottom toolbar zoom/mode controls work
- [ ] Keyboard shortcuts (Ctrl+Z, Ctrl+S, V, H) work
- [ ] Save button persists changes

**Step 5: Final commit**

```bash
git commit -m "feat(workflow): complete UI/UX overhaul - Attio-style builder"
```

---

## Dependency Graph

```
Task 1 (Lifecycle Enum) ──→ Task 4 (Lifecycle API) ──→ Task 10 (Top Bar)
                                                    ──→ Task 15 (Filament)
Task 2 (Output Schema) ──→ Task 9 (Variable Picker)
Task 3 (Run API) ──→ Task 11 (Run History)

Task 5 (Blade Layout) ──→ Task 7 (Config Panel)
                      ──→ Task 8 (Block Picker)
                      ──→ Task 10 (Top Bar)
                      ──→ Task 11 (Run History)
                      ──→ Task 12 (Bottom Toolbar)

Task 6 (Node Shapes) ──→ Task 14 (Wire Together)
Task 13 (CSS) ──→ Task 14 (Wire Together)

Tasks 7-12 ──→ Task 14 (Wire Together) ──→ Task 16 (Final Test)
```

**Parallelizable groups:**
- Group A (backend): Tasks 1, 2, 3 can run in parallel
- Group B (depends on A): Task 4
- Group C (frontend foundation): Tasks 5, 6, 13 can run in parallel
- Group D (frontend features, depends on C): Tasks 7, 8, 9, 10, 11, 12 can partially parallelize
- Group E (integration): Tasks 14, 15, 16 are sequential
