# Workflow Automation Module — Design Document

**Date:** 2026-02-28
**Package:** `relaticle/workflow`
**Status:** Approved

## Overview

A visual workflow automation module for Relaticle (and any Laravel app) that lets users build trigger-action workflows using a drag-and-drop node canvas. Users define workflows visually — when X happens, do Y — with support for conditional branching, delays, loops, and variable interpolation.

## Key Decisions

| Decision | Choice |
|---|---|
| **Visual canvas library** | AntV X6 (vanilla JS, 6.5k stars, MIT, HTML custom nodes) |
| **Backend approach** | Custom engine (no external workflow/BPMN libs) |
| **Execution model** | Async via Laravel queues (Redis/Horizon) |
| **Scoping** | Per-team multi-tenancy (configurable, optional) |
| **Logging** | Full audit trail with per-step input/output |
| **Package structure** | Single package `relaticle/workflow` with optional Filament adapter |
| **Testing** | TDD with Pest feature tests + Pest browser tests (Playwright) |
| **Filament integration** | Conditional — only loads if Filament is installed |

## Triggers (V1)

### Record Events
- Record created (Company, People, Opportunity, Task, or any registered model)
- Record updated (any field change)
- Specific field changed to a value
- Record deleted

### Time-Based
- Scheduled (cron expression — daily, weekly, monthly)
- Date field (X days before/after a date field value)
- Inactivity (no update in X days)

### Manual
- User clicks "Run Workflow" button on a record page

### Webhook
- Incoming POST to a unique workflow endpoint

## Actions (V1)

### CRM Actions
- Create record
- Update record (set field values)
- Delete record
- Move stage (kanban board column)
- Assign user
- Add note to record
- Set custom field value

### Notifications
- Send email notification
- Send in-app notification
- Send webhook (POST to URL)
- Send Slack message (via webhook)

### Logic Nodes
- If/Else condition branching
- Delay (wait X minutes/hours/days)
- Loop (for each related record)
- Variable interpolation `{{record.name}}`
- Stop workflow

## Data Model

### workflows
| Column | Type | Notes |
|---|---|---|
| id | ULID | Primary key |
| team_id | FK → configurable | Nullable, for tenancy |
| creator_id | FK → users | Nullable |
| name | string | Required |
| description | text | Nullable |
| trigger_type | enum | record_event, time_based, manual, webhook |
| trigger_config | JSON | Entity type, field, schedule, etc. |
| canvas_data | JSON | X6 graph serialization for visual editor |
| is_active | boolean | Default false |
| last_triggered_at | timestamp | Nullable |
| timestamps | | |
| soft_deletes | | |

### workflow_nodes
| Column | Type | Notes |
|---|---|---|
| id | ULID | Primary key |
| workflow_id | FK → workflows | Cascade delete |
| node_id | string | X6 node identifier |
| type | enum | trigger, action, condition, delay, loop, stop |
| action_type | string | Nullable — e.g., "create_record", "send_email" |
| config | JSON | Action-specific parameters, variable templates |
| position_x | integer | Canvas X position |
| position_y | integer | Canvas Y position |
| timestamps | | |

### workflow_edges
| Column | Type | Notes |
|---|---|---|
| id | ULID | Primary key |
| workflow_id | FK → workflows | Cascade delete |
| edge_id | string | X6 edge identifier |
| source_node_id | FK → workflow_nodes | |
| target_node_id | FK → workflow_nodes | |
| condition_label | string | Nullable — "yes"/"no" for branches |
| condition_config | JSON | Nullable — evaluation rules |
| timestamps | | |

### workflow_runs
| Column | Type | Notes |
|---|---|---|
| id | ULID | Primary key |
| workflow_id | FK → workflows | |
| trigger_record_type | string | Nullable — morphable type |
| trigger_record_id | ULID | Nullable — morphable id |
| status | enum | pending, running, completed, failed, cancelled |
| started_at | timestamp | |
| completed_at | timestamp | Nullable |
| error_message | text | Nullable |
| context_data | JSON | Runtime variables, resolved trigger data |
| timestamps | | |

### workflow_run_steps
| Column | Type | Notes |
|---|---|---|
| id | ULID | Primary key |
| workflow_run_id | FK → workflow_runs | Cascade delete |
| workflow_node_id | FK → workflow_nodes | |
| status | enum | pending, running, completed, failed, skipped |
| input_data | JSON | Nullable |
| output_data | JSON | Nullable |
| error_message | text | Nullable |
| started_at | timestamp | |
| completed_at | timestamp | Nullable |
| timestamps | | |

## Package Structure

```
relaticle/workflow/
├── src/
│   ├── WorkflowServiceProvider.php
│   ├── WorkflowManager.php                — registry (models, actions, tenancy config)
│   ├── Facades/
│   │   └── Workflow.php
│   │
│   ├── Models/
│   │   ├── Workflow.php
│   │   ├── WorkflowNode.php
│   │   ├── WorkflowEdge.php
│   │   ├── WorkflowRun.php
│   │   └── WorkflowRunStep.php
│   │
│   ├── Enums/
│   │   ├── TriggerType.php
│   │   ├── NodeType.php
│   │   ├── WorkflowRunStatus.php
│   │   └── StepStatus.php
│   │
│   ├── Engine/
│   │   ├── WorkflowExecutor.php           — walks graph, dispatches step jobs
│   │   ├── GraphWalker.php                — topological traversal logic
│   │   ├── VariableResolver.php           — resolves {{record.name}}, {{now}}, etc.
│   │   └── ConditionEvaluator.php         — evaluates if/else conditions
│   │
│   ├── Actions/
│   │   ├── Contracts/
│   │   │   └── WorkflowAction.php         — interface host apps implement
│   │   ├── BaseAction.php
│   │   ├── SendWebhookAction.php
│   │   ├── SendEmailAction.php
│   │   ├── DelayAction.php
│   │   └── HttpRequestAction.php
│   │
│   ├── Triggers/
│   │   ├── Contracts/
│   │   │   └── WorkflowTrigger.php
│   │   ├── RecordEventTrigger.php
│   │   ├── ScheduledTrigger.php
│   │   ├── ManualTrigger.php
│   │   └── WebhookTrigger.php
│   │
│   ├── Jobs/
│   │   ├── ExecuteWorkflowJob.php
│   │   ├── ExecuteStepJob.php
│   │   └── EvaluateScheduledWorkflowsJob.php
│   │
│   ├── Observers/
│   │   └── WorkflowModelObserver.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── WorkflowApiController.php
│   │   │   └── WebhookTriggerController.php
│   │   └── Middleware/
│   │       └── WorkflowTenancyMiddleware.php
│   │
│   ├── Events/
│   │   ├── WorkflowTriggered.php
│   │   ├── WorkflowRunCompleted.php
│   │   └── WorkflowRunFailed.php
│   │
│   └── Filament/                          — only loads if Filament is installed
│       ├── WorkflowPlugin.php
│       ├── Resources/
│       │   └── WorkflowResource.php
│       │       └── Pages/
│       │           ├── ListWorkflows.php
│       │           ├── CreateWorkflow.php
│       │           ├── EditWorkflow.php
│       │           └── WorkflowBuilder.php
│       └── Widgets/
│           └── WorkflowStatsWidget.php
│
├── config/
│   └── workflow.php
│
├── database/
│   └── migrations/
│       ├── create_workflows_table.php
│       ├── create_workflow_nodes_table.php
│       ├── create_workflow_edges_table.php
│       ├── create_workflow_runs_table.php
│       └── create_workflow_run_steps_table.php
│
├── resources/
│   ├── js/
│   │   └── workflow-builder/
│   │       ├── index.js
│   │       ├── graph.js
│   │       ├── nodes/
│   │       │   ├── TriggerNode.js
│   │       │   ├── ActionNode.js
│   │       │   ├── ConditionNode.js
│   │       │   ├── DelayNode.js
│   │       │   ├── LoopNode.js
│   │       │   └── StopNode.js
│   │       ├── sidebar.js
│   │       ├── toolbar.js
│   │       └── config-panel.js
│   ├── css/
│   │   └── workflow-builder.css
│   └── views/
│       └── builder.blade.php
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── tests/
│   ├── Feature/
│   │   ├── WorkflowRegistrationTest.php
│   │   ├── RecordEventTriggerTest.php
│   │   ├── ScheduledTriggerTest.php
│   │   ├── ManualTriggerTest.php
│   │   ├── WebhookTriggerTest.php
│   │   ├── WorkflowExecutionTest.php
│   │   ├── ConditionEvaluatorTest.php
│   │   ├── DelayActionTest.php
│   │   ├── LoopActionTest.php
│   │   ├── VariableResolutionTest.php
│   │   ├── WorkflowAuditTrailTest.php
│   │   ├── TenancyScopingTest.php
│   │   ├── WorkflowResourceTest.php
│   │   └── CanvasApiTest.php
│   │
│   └── Browser/
│       ├── WorkflowCanvasTest.php
│       ├── NodeDragDropTest.php
│       ├── NodeConnectionTest.php
│       ├── NodeConfigPanelTest.php
│       └── WorkflowVisualRegressionTest.php
│
├── composer.json
├── phpunit.xml
├── testbench.yaml
└── vite.config.js
```

## Execution Flow

```
1. TRIGGER fires (observer / scheduler / manual / webhook)
       │
2. ExecuteWorkflowJob dispatched to queue
       │
3. WorkflowExecutor loads workflow graph (nodes + edges)
       │
4. GraphWalker walks from trigger node → follows edges
       │
5. Per node: ExecuteStepJob
   ├── Action node → run Action class, log step result
   ├── Condition node → ConditionEvaluator picks branch edge
   ├── Delay node → re-dispatch ExecuteStepJob with ->delay()
   ├── Loop node → iterate related records, run sub-path per item
   └── Stop node → mark run as completed
       │
6. Each step logs to workflow_run_steps (input/output/status/timing)
       │
7. On completion/failure → WorkflowRunCompleted / WorkflowRunFailed event
```

## Host App Integration API

```php
// Register triggerable models
Workflow::registerTriggerableModel(Company::class, [
    'label' => 'Company',
    'events' => ['created', 'updated', 'deleted'],
    'fields' => fn () => [
        'name' => ['type' => 'string', 'label' => 'Name'],
        'domain' => ['type' => 'string', 'label' => 'Domain'],
    ],
]);

// Register custom actions
Workflow::registerAction('move_stage', MoveStageAction::class);
Workflow::registerAction('assign_user', AssignUserAction::class);

// Configure tenancy (optional)
Workflow::useTenancy(
    scopeColumn: 'team_id',
    resolver: fn () => auth()->user()?->currentTeam?->id,
);

// Filament panel plugin (optional)
->plugins([
    WorkflowPlugin::make(),
])
```

## Test Plan (TDD — Feature Tests Only)

### Engine Tests (Pest + Orchestra Testbench)

**WorkflowRegistrationTest** — registers models, actions, tenancy; rejects invalid action classes

**RecordEventTriggerTest** — triggers on create/update/delete/field change; skips inactive and wrong-tenant; passes record as context; fires multiple matching workflows

**ScheduledTriggerTest** — cron evaluation; date-field X days before; inactivity detection; no false triggers

**ManualTriggerTest** — API-triggered execution; auth check; record passed as context

**WebhookTriggerTest** — POST trigger; payload as context; 404 for unknown webhook

**WorkflowExecutionTest** — linear execution; branching; yes/no paths; stop node; failure handling; nested conditions

**ConditionEvaluatorTest** — equals, not equals, contains, greater/less than, empty, in list, AND/OR compounds, variable resolution in values

**DelayActionTest** — delays next step; resumes after; audit trail timing

**LoopActionTest** — iterates hasMany; sub-path per item; loop.item and loop.index variables; empty collection

**VariableResolutionTest** — record fields, custom fields, date variables, trigger user, nested relations, missing variables

**WorkflowAuditTrailTest** — run creation; step logging with input/output; timing; failure/skip/complete states

**TenancyScopingTest** — query scoping; cross-tenant prevention; auto tenant attachment; works without tenancy

### Filament Tests (Pest + Testbench)

**WorkflowResourceTest** — list, create, edit, toggle active, delete, run history

**CanvasApiTest** — save/load canvas JSON, node/edge sync, validation, tenant scoping, returns registered models/actions

### Browser Tests (Pest + Playwright)

**WorkflowCanvasTest** — renders X6 canvas; loads saved graph; saves on button click; empty state

**NodeDragDropTest** — drag trigger/action/condition from sidebar; correct positioning; no duplicate triggers

**NodeConnectionTest** — connect nodes via ports; connection lines render; delete edges; condition labels; prevent invalid connections

**NodeConfigPanelTest** — opens on node click; action type dropdown; field selector; saves config; variable autocomplete

**WorkflowVisualRegressionTest** — screenshot match: empty canvas, 3-node workflow, branching layout

## Frontend Architecture (X6 Canvas)

The visual builder is a standalone vanilla JS application embedded in a Filament page via `wire:ignore`. Communication with Laravel is through API endpoints (save/load canvas data).

**Node Types** are registered as X6 HTML nodes with custom rendering:
- **TriggerNode** — green header, shows trigger type and entity
- **ActionNode** — blue header, shows action type and summary
- **ConditionNode** — yellow diamond-style, shows condition expression
- **DelayNode** — gray with clock icon, shows duration
- **LoopNode** — purple with repeat icon, shows collection
- **StopNode** — red circle, terminal node

**Sidebar** — draggable palette of available node types
**Toolbar** — undo/redo, zoom, save, toggle active
**Config Panel** — right-side panel that opens when clicking a node, shows configurable fields

## Configuration (config/workflow.php)

```php
return [
    'queue' => env('WORKFLOW_QUEUE', 'default'),
    'table_prefix' => env('WORKFLOW_TABLE_PREFIX', ''),
    'max_steps_per_run' => env('WORKFLOW_MAX_STEPS', 100),
    'max_loop_iterations' => env('WORKFLOW_MAX_LOOP', 500),
    'retry_attempts' => env('WORKFLOW_RETRY_ATTEMPTS', 3),
    'enable_audit_trail' => true,
];
```
