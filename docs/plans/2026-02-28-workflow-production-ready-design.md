# Workflow Package: Production-Ready Design

## Context

The workflow package provides visual workflow automation for CRM users. It has a solid
architecture (~70-80% complete) with an X6-based visual builder, BFS graph execution engine,
Filament admin integration, and extensible action/trigger system.

This design addresses the remaining ~20-30% needed for production readiness: tenant isolation,
execution reliability, security hardening, UX polish, and real async delay support.

## Constraints

- Multi-tenant SaaS: tenant isolation is critical (data leaks are a dealbreaker)
- Both record-event and scheduled triggers must work from day one
- Delay nodes must actually delay execution (not just return metadata)
- Builder UX and backend reliability ship together
- Layer-by-layer approach: Database -> Engine -> Actions -> API -> Filament -> Frontend

## Phase 1: Database & Models

### 1.1 Tenant scoping
Add a `BelongsToTenant` trait or global scope applied to `Workflow` and `WorkflowRun` models.
Every query automatically scoped to the authenticated tenant.

### 1.2 Database constraints
- Add NOT NULL constraints on critical fields (node `type`, edge `source_node_id`/`target_node_id`)
- Add composite indexes on `(tenant_id, is_active)` for frequent workflow lookups

### 1.3 Optimistic locking
Add `canvas_version` column to `workflows` table. Canvas save checks version match before
writing, preventing race conditions with concurrent editors.

### 1.4 Remove placeholder code
Either implement `ExecuteStepJob` (needed for delay support) or remove the empty class.
No empty placeholder classes in production.

## Phase 2: Engine & Execution

### 2.1 Transaction wrapping
Wrap `WorkflowExecutor::execute()` in a database transaction so partial step creation is
rolled back on failure.

### 2.2 Real delay implementation
Rework `DelayAction` to dispatch a delayed job that continues the workflow:
- Implement `ExecuteStepJob` to resume execution from a specific node
- Split `walkGraph()` to support resumption from a checkpoint
- Store "paused at node X" state on `WorkflowRun` (new `paused` status)
- `WorkflowRunStatus` gains a `paused` value

### 2.3 Strict condition evaluation
- Fix `in` operator to use strict comparison (`in_array($value, $list, true)`)
- Log warnings when variables can't be resolved instead of silent empty strings

### 2.4 Loop context namespacing
Prefix loop variables to avoid collisions with existing context keys. Check for
collisions before overwriting.

### 2.5 Step timeout enforcement
Add configurable per-step timeout so hung HTTP requests don't block entire runs.
Default: 30 seconds.

## Phase 3: Actions, Triggers & Security

### 3.1 Webhook security
- Generate `webhook_secret` per workflow
- Validate `X-Signature` header with HMAC-SHA256 verification
- Reject unsigned requests

### 3.2 Action config validation
Actions define `configSchema()` but nothing enforces it. Add validation in
`WorkflowExecutor::executeActionNode()` using Laravel's Validator before running.

### 3.3 Tenant-scoped trigger queries
- `RecordEventTrigger::getMatchingWorkflows()` must scope to correct tenant
- `EvaluateScheduledWorkflowsJob` must scope to tenant
- Observer passes tenant context through

### 3.4 Email action hardening
- Try/catch around `Mail::send`
- Validate `to` is a valid email address
- Configurable from address

### 3.5 HTTP action safety
- Configurable timeout (default 30s) for `HttpRequestAction` and `SendWebhookAction`
- Response size limits
- Disallow private IP ranges (SSRF prevention)

## Phase 4: API & Controllers

### 4.1 Canvas validation
- Validate edge source/target nodes exist in submitted nodes
- Validate node types against `NodeType` enum
- Reject invalid graph structures

### 4.2 Authorization
- Wire up `WorkflowPolicy` for proper authorization checks
- Scope all canvas API endpoints to workflow owner/tenant
- Return 403 for unauthorized access

### 4.3 API error responses
Standardize error responses with proper HTTP status codes and structured JSON bodies.

### 4.4 Canvas metadata optimization
Cache available models/actions/schemas metadata or lazy-load via separate endpoints.

## Phase 5: Filament UI

### 5.1 Tenant-scoped stats widget
`WorkflowStatsWidget` must scope queries to current team/tenant.

### 5.2 Workflow run history page
Add `ViewWorkflow` page showing run history: status, duration, trigger info, timestamps.

### 5.3 Dynamic trigger config form
Trigger config form should be dynamic based on trigger type:
- Record event: model selector + event type + field filters
- Scheduled: cron expression input + timezone
- Manual/webhook: minimal config

### 5.4 Activation safeguards
Prevent activating a workflow with:
- No nodes
- No trigger node
- Incomplete action configs
Show validation errors explaining what's missing.

### 5.5 Run detail view
Drill into a run to see each step: status, input/output data, timing, errors.
Essential for user debugging.

## Phase 6: Frontend Builder

### 6.1 Form validation
Config panel validates required fields before save. Highlight missing fields,
disable save button until valid.

### 6.2 Toast notifications
Replace button text change with proper toast notifications for save success,
save failure, and validation errors.

### 6.3 Delete confirmation
Confirmation dialog when deleting nodes, especially trigger nodes.

### 6.4 Node status indicators
Visual feedback on nodes: green if configured, yellow if incomplete, red if invalid.

### 6.5 Better config panel UX
- JSON fields get key-value pair inputs instead of raw textareas
- Dynamic field rendering based on action config schemas

### 6.6 Accessible icons
Replace Unicode characters with proper SVG icons that render consistently.

### 6.7 Error recovery
- Canvas load failure: show error state with retry button
- Save failure: preserve unsaved changes, show error
- Network errors: graceful degradation

## Out of Scope (Post-Launch)

- Workflow versioning (changes don't break in-progress runs)
- Workflow templates (pre-built starting points)
- Dark mode / mobile responsiveness
- Workflow pause/resume from UI
- Manual step retries from run detail view
- API versioning (/api/v1/ namespace)
