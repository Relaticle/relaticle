# Relaticle-Native Workflow Engine — Full Overhaul Design

**Date:** 2026-03-01
**Status:** Approved
**Scope:** Full overhaul — engine, config panel, run viewer, list, settings, new block types
**Approach:** Bottom-up (engine → config panel → run viewer → polish)

## Context

The workflow package (`packages/workflow/`) was built as a generic, reusable Laravel package. This creates friction: the config panel uses basic HTML inputs because it can't know Relaticle's schema, the variable system uses raw `{{record.field}}` text, and record operations are impossible without direct model access.

This design makes the engine Relaticle-aware — directly using Eloquent models, custom fields, and Filament form components. Permissions are deferred to a future iteration.

## Architecture Decisions

- **Config panel**: Livewire component with Filament forms (not Alpine.js HTML)
- **Variable system**: Schema-aware with relationship traversal and styled chips
- **Actions**: Direct Eloquent operations on People, Company, Opportunity, Task, Note
- **Run viewer**: Canvas status overlays + block detail popover (data already in WorkflowRunStep)
- **No permissions**: All team members can manage all workflows (deferred)

---

## Phase 1: Relaticle-Native Engine

### 1.1 Entity Registry (`RelaticleSchema`)

A service class that provides schema information for all 5 entity types.

**Entities:** People, Company, Opportunity, Task, Note

**For each entity provides:**
- Model class, display name, table name
- Standard fields with types (e.g., People: name/string, company_id/relation)
- Custom fields queried from `CustomField` model filtered by `entity_type`
- Relationships (Company→People, Opportunity→Company, People→Company, etc.)
- Field metadata: type, required, options (for selects), validation rules

**Usage:** Actions and the config panel call `RelaticleSchema::getFields('people')` to get available fields for dynamic form generation.

### 1.2 Variable Resolution Upgrade

Replace the current flat `{{record.name}}` resolver with a structured variable system.

**Variable paths:**
- `trigger.record.{field}` — triggering record's standard field
- `trigger.record.custom.{code}` — custom field value by code
- `trigger.record.{relation}.{field}` — relationship traversal (e.g., `trigger.record.company.name`)
- `steps.{nodeId}.output.{key}` — output from a previous step
- `now`, `today` — built-in

**Context building:** The executor builds a structured context object as it walks the graph. Each completed step's output is added to `context.steps.{nodeId}.output`.

**Variable catalog:** An API endpoint returns all available variables at a given point in the workflow graph (based on which steps precede the current node).

### 1.3 New Action Classes

All actions directly use Eloquent. Each defines a Filament form schema for its config.

| Action | Description | Config |
|--------|-------------|--------|
| `CreateRecordAction` | Creates a People/Company/Opportunity/Task/Note | Entity type select, field mapping (standard + custom) |
| `UpdateRecordAction` | Updates fields on a record | Record source (trigger/found), field mapping |
| `FindRecordAction` | Queries records by conditions | Entity type, conditions (field/operator/value), limit |
| `DeleteRecordAction` | Soft-deletes a record | Record source (trigger/found) |
| `UpdateCustomFieldAction` | Updates a specific custom field | Entity type, record source, field select, value |
| `SendEmailAction` | Sends email (existing, upgraded) | To, Subject, Body — all with variable picker |
| `SendWebhookAction` | Sends webhook (existing) | URL, payload |
| `HttpRequestAction` | HTTP request (existing) | Method, URL, headers, body |

### 1.4 Remove Generic Registration

- Remove `WorkflowManager::registerTriggerableModel()` and `registerAction()`
- Remove `WorkflowServiceProvider` model/action registration code
- Actions are discovered directly from a hardcoded registry in the package
- Trigger models are the 5 Relaticle entities, hardcoded in `RelaticleSchema`

---

## Phase 2: Livewire Config Panel

### 2.1 Architecture

The right sidebar becomes a Livewire component (`WorkflowConfigPanel`) embedded in `builder.blade.php`.

**Event flow:**
1. Alpine (canvas): user clicks node → dispatches browser event `wf:node-selected` with `{nodeId, nodeType, actionType}`
2. Livewire: listens via `@on('wf:node-selected')`, loads `WorkflowNode` config from DB
3. Livewire: renders Filament form dynamically based on node type + action type
4. User edits form → Livewire auto-saves to `WorkflowNode.config`
5. Livewire: dispatches `wf:node-updated` back to Alpine with updated config
6. Alpine: updates the canvas node display (label, description)

### 2.2 Dynamic Form Generation

Each action type returns a Filament form schema from a static method:

```
SendEmailAction::configForm(): array of Filament Components
CreateRecordAction::configForm(): array of Filament Components
```

The `WorkflowConfigPanel` Livewire component calls the appropriate action's `configForm()` and renders it.

### 2.3 Entity Field Selector

A custom Filament Select component (`EntityFieldSelect`) that:
1. Shows entity type dropdown (People, Company, etc.)
2. On selection, queries `RelaticleSchema::getFields($entityType)` for standard + custom fields
3. Returns field options grouped by "Standard Fields" and "Custom Fields"
4. Supports relationship traversal: People → Company → fields (nested select)

### 2.4 Variable Picker

A Filament Action button (`{x}`) attached to text inputs that opens a modal:
- Groups available variables by source: "Trigger Record", "Step: Send Email", "Built-in"
- Each group lists available fields with types
- Selecting inserts a styled variable reference into the input
- Available variables computed from the graph topology (only preceding steps)

### 2.5 Block Type Selector ("Change" button)

Header of the config panel shows current block icon + category + name, with a "Change" button that opens the block picker to swap the action type without deleting the node.

---

## Phase 3: Run Viewer Overhaul

### 3.1 Canvas Status Overlays

When entering run view mode, each canvas node gets a status badge:
- **Green pill**: "Completed" with checkmark icon
- **Red pill**: "Failed" with X icon
- **Gray pill**: "Skipped"
- **Blue animated pill**: "Running"

Implementation: X6 node HTML template adds a status badge `<span>` positioned at the top-right of the node card. Status comes from `WorkflowRunStep.status` matched by `WorkflowNode.node_id`.

### 3.2 Edge Path Coloring

Edges in run view change color based on the flow:
- **Green**: Both source and target steps completed
- **Red**: Source completed but target failed
- **Gray**: Path not traversed (e.g., "No" branch of a condition that evaluated to "Yes")

### 3.3 Run List Sidebar

Replaces current simple timestamp list:
- **Header**: Status summary badge ("Completed 12" or "Failed 3")
- **Entries**: Sequential "Run #N" with colored dot, relative timestamp, duration
- **Click**: Loads that run's step data onto canvas
- **Pagination**: Load more button (API already returns last 50)

### 3.4 Block Detail Popover

Clicking a node in run view shows a dark tooltip popover:
- **Status**: Colored badge
- **Runtime**: Formatted duration (started_at → completed_at)
- **Timestamps**: Started at, Completed at
- **Inputs**: Resolved config values from `WorkflowRunStep.input_data` (JSON pretty-printed)
- **Outputs**: Action return data from `WorkflowRunStep.output_data`
- **Error**: Error message if failed (red text)

Implementation: An Alpine.js popover component positioned relative to the clicked node. Data fetched from existing `GET /workflow/api/workflow-runs/{run}` endpoint which already returns steps with input/output data.

---

## Phase 4: Workflow List & Settings Polish

### 4.1 Workflow List Improvements

**Two-line row layout:** Name (bold) + description (gray, truncated) in a single composite column using a custom Filament column view.

**Created by column:** User avatar + name via `creator` relationship (already exists as `creator_id` FK).

**Formatted run counts:** `withCount('runs')` on the query, displayed as "2,716 runs" with number formatting.

**Enhanced grouping:** Add group-by options: "Created by" (creator.name), "Creation date" (by month).

**Favorites:** New `workflow_favorites` pivot table (user_id, workflow_id). Star icon toggle per row. Filter "My favorites" option.

### 4.2 Settings Tab Enrichment

**Execution limits:** "Maximum steps per run" number input, stored in `Workflow.trigger_config.max_steps` (default 100). Executor reads this instead of hardcoded value.

**Failure notifications:** Toggle "Notify on failure" stored in `Workflow.trigger_config.notify_on_failure`. When a run fails, executor dispatches `WorkflowRunFailed` notification to the workflow's creator.

**Run count badge:** "Runs (3)" label on the Runs tab, count fetched from API.

### 4.3 Migration

One new migration:
- `workflow_favorites` table: `id`, `user_id` (FK), `workflow_id` (FK), `created_at`. Unique index on (user_id, workflow_id).

---

## Data Flow Summary

```
User clicks node on canvas (Alpine/X6)
  → dispatches 'wf:node-selected' browser event
  → Livewire WorkflowConfigPanel receives event
  → Loads WorkflowNode from DB
  → Calls Action::configForm() for Filament schema
  → Renders dynamic form with entity field selectors + variable pickers
  → User edits → Livewire saves to WorkflowNode.config
  → Dispatches 'wf:node-updated' back to Alpine
  → Alpine updates canvas node display
```

```
User saves workflow (Alpine)
  → POST /workflow/api/workflows/{id}/canvas
  → CanvasController syncs nodes/edges to DB
  → Returns new canvas_version
```

```
Workflow executes (Engine)
  → WorkflowExecutor walks graph (BFS)
  → For each node: resolves config via VariableResolver
  → Executes action (Eloquent operations for CRUD actions)
  → Records WorkflowRunStep with input_data + output_data
  → Adds step output to context for downstream variable resolution
```

```
User views run (Run Viewer)
  → GET /workflow/api/workflow-runs/{run} (steps with input/output data)
  → Canvas nodes get status badge overlays
  → Edges colored green/red/gray based on step statuses
  → Click node → popover shows step detail (inputs, outputs, runtime, errors)
```

## Out of Scope (Deferred)

- Workflow sharing/permissions (4-tier access control)
- AI/Agent block types
- Calculation blocks (Aggregate, Formula)
- Card view with mini canvas thumbnails on workflow list
- Inline filter/sort pill badges (using Filament's built-in filter panel instead)
