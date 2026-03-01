# Workflow UI/UX Overhaul — Design Document

**Date:** 2026-03-01
**Scope:** Phase 1 — UI/UX overhaul to match Attio-style workflow builder
**Stack:** Alpine.js + AntV X6 (enhanced), Filament integration
**Status:** Draft

---

## 1. Overview

Complete visual overhaul of the workflow builder to match Attio's automation UX patterns. The current left-sidebar + canvas + right-panel layout will be replaced with an Attio-style full-width canvas with a collapsible right configuration panel. The block library moves from a permanent sidebar to an inline `+` button popover.

### Design Principles

- **Canvas-first**: The graph canvas is the primary workspace, taking maximum screen space
- **Contextual configuration**: Right panel appears only when a block is selected
- **Progressive disclosure**: Block library appears via `+` buttons, not a permanent sidebar
- **Data flow visibility**: Variable outputs flow between blocks and are selectable via `{x}` picker
- **Lifecycle awareness**: Draft → Live → Paused → Archived with explicit publish

---

## 2. Layout Architecture

```
┌──────────────────────────────────────────────────────────┐
│ ← Back    Workflow Name (editable)    [Runs] [⚙] [Publish] │  ← Top Bar
├──────────────────────────────────────┬───────────────────┤
│                                      │                   │
│                                      │   Right Panel     │
│            Canvas                    │   (Config)        │
│            (AntV X6)                 │                   │
│                                      │   - Block inputs  │
│         [Trigger Block]              │   - Variable {x}  │
│              │                       │   - Settings      │
│           [+ Add]                    │                   │
│              │                       │                   │
│         [Action Block]               │                   │
│              │                       │                   │
│           [+ Add]                    │                   │
│                                      │                   │
├──────────────────────────────────────┴───────────────────┤
│  [V Pointer] [H Drag] [🔍 Zoom] [⊞ Organize] [📝 Note] │  ← Bottom Toolbar
└──────────────────────────────────────────────────────────┘
```

### Top Bar
- **Back arrow**: Returns to workflow list
- **Workflow name**: Inline-editable text
- **Runs button**: Opens run history sidebar/modal
- **Settings gear**: Opens workflow-level settings (description, notifications)
- **Publish button**: Primary CTA, publishes draft → live. Shows "Publish Changes" for live workflows with edits

### Canvas (Center)
- Full-width minus right panel (when open)
- AntV X6 graph with enhanced node shapes
- Pan: trackpad scroll (pointer mode) or click-drag (drag mode)
- Zoom: Ctrl+scroll, toolbar buttons
- Grid: subtle dot pattern

### Right Panel (320px, collapsible)
- **When no block selected**: Shows workflow-level settings (name, description, status)
- **When block selected**: Shows block configuration with typed inputs and variable picker
- **Collapse**: Clicking canvas background collapses panel
- **Back arrow**: Returns from block config to workflow settings

### Bottom Toolbar
- Pointer mode (V) / Drag mode (H) toggle
- Zoom in/out/reset
- Organize blocks (auto-layout)
- Add note (canvas annotation)

---

## 3. Block System (Visual Nodes)

### 3.1 Block Categories & Colors

| Category | Color | Icon | Blocks |
|----------|-------|------|--------|
| Triggers | Green (#22c55e) | ⚡ | Record Created, Record Updated, Manual, Webhook, Scheduled |
| Actions | Blue (#3b82f6) | ▶ | Send Email, Send Webhook, HTTP Request |
| Logic | Amber (#f59e0b) | ◇ | Condition (If/Else), Filter (Gate), Switch |
| Timing | Slate (#64748b) | ⏱ | Delay, Delay Until |
| Flow | Purple (#8b5cf6) | ↻ | Loop |
| Terminal | Red (#ef4444) | ■ | Stop |

### 3.2 Block Shape Design

Each block is a rounded rectangle (240px wide) with:

```
┌─────────────────────────────────┐
│ ● [Icon] Block Type Name        │  ← Header (colored by category)
│                                 │
│  Summary of configuration       │  ← Body (white/dark background)
│  e.g. "When company created"    │
│                                 │
│            [+]                  │  ← Add next block button
└─────────────────────────────────┘
```

**Port positions:**
- Input port: top-center (hidden on trigger blocks)
- Output port: bottom-center (hidden on stop blocks)
- Condition blocks: two output ports (Yes/No) at bottom-left and bottom-right
- Switch blocks: N output ports at bottom, evenly spaced

**Visual states:**
- Default: solid border, category color header
- Selected: blue glow/ring
- Error/incomplete: red dashed border
- Disabled: opacity 50%
- Running (during run view): pulsing border animation
- Completed (during run view): green checkmark badge
- Failed (during run view): red X badge
- Skipped (during run view): grey, strikethrough

### 3.3 The `+` Button (Block Insertion)

- Appears on hover between connected blocks and at the end of chains
- Clicking opens a **popover block picker** organized by category
- The picker shows categorized blocks with search
- Selecting a block inserts it at that position and auto-connects edges

```
         [Trigger Block]
              │
           [+ Add] ← hover to reveal
              │
     ┌────────────────────┐
     │ 🔍 Search blocks   │  ← Block Picker Popover
     ├────────────────────┤
     │ ▸ Records          │
     │   Create Record    │
     │   Update Record    │
     │   Find Records     │
     │ ▸ Logic            │
     │   If/Else          │
     │   Filter           │
     │ ▸ Actions          │
     │   Send Email       │
     │   HTTP Request     │
     │ ▸ Timing           │
     │   Delay            │
     └────────────────────┘
```

---

## 4. Right Panel — Block Configuration

### 4.1 Panel Structure

```
┌─────────────────────────┐
│ ← Back    Block Type    │  ← Header with back arrow
├─────────────────────────┤
│                         │
│ [Section: Inputs]       │
│                         │
│ Field Label             │
│ ┌─────────────── {x} ┐ │  ← Input with variable picker
│ │ value or variable   │ │
│ └─────────────────────┘ │
│                         │
│ Field Label             │
│ ┌─────────────── {x} ┐ │
│ │ Select...           │ │
│ └─────────────────────┘ │
│                         │
│ [Section: Outputs]      │
│ • output_name (type)    │  ← Shows what this block produces
│                         │
│ [Delete Block]          │
└─────────────────────────┘
```

### 4.2 Variable Picker (`{x}`)

When clicking the `{x}` icon on any input field:

```
┌──────────────────────────────┐
│ 🔍 Search variables          │
├──────────────────────────────┤
│ ▸ Trigger: Record Created    │
│   record.name                │
│   record.email               │
│   record.company_id          │
│   record.created_at          │
│ ▸ Step 2: Find Records       │
│   results (array)            │
│   results.0.name             │
│ ▸ Built-in                   │
│   {{now}}                    │
│   {{today}}                  │
└──────────────────────────────┘
```

- Groups variables by their source block
- Shows data type (string, number, array, boolean)
- Inserted variables render as colored chips/pills in the input
- Variables displayed as `{{block_name.output_key}}`

### 4.3 Trigger Configuration Examples

**Record Created:**
- Object: dropdown (Company, Person, Opportunity, Task, Note)

**Record Updated:**
- Object: dropdown
- Attribute filter: optional dropdown of fields
- When attribute selected: previous and new values become available as variables

**Scheduled:**
- Frequency: Daily / Weekly / Monthly / Custom
- If Daily: Time picker
- If Weekly: Day picker + Time picker
- If Monthly: Date picker + Time picker
- If Custom: Cron expression input

**Webhook:**
- Auto-generated URL (read-only, copy button)
- Optional secret for HMAC validation

### 4.4 Action Configuration Examples

**Send Email:**
- To: text input with `{x}` variable picker
- Subject: text input with `{x}`
- Body: rich text / textarea with `{x}`

**HTTP Request:**
- Method: GET/POST/PUT/PATCH/DELETE
- URL: text input with `{x}`
- Headers: key-value pairs (add/remove rows)
- Body: JSON textarea with `{x}`

**Condition (If/Else):**
- Field: variable picker or dot-notation
- Operator: equals, not_equals, contains, greater_than, less_than, is_empty, is_not_empty, in
- Value: text input with `{x}`
- Logic: AND/OR toggle for multiple conditions

---

## 5. Workflow Lifecycle

### 5.1 States

```
  [Draft] ──publish──→ [Live] ──pause──→ [Paused]
     ↑                    │                  │
     │                    │                  │
     └────edit────────────┘     archive      │
                                  │          │
                                  ↓          │
                             [Archived] ←────┘
                                  │
                              restore
                                  │
                                  ↓
                             [Paused]
```

- **Draft**: Initial state. Being configured. Cannot trigger.
- **Live**: Published and actively listening for triggers.
- **Paused**: Stopped accepting new triggers. In-progress runs complete.
- **Archived**: Hidden from default list. Also paused.

### 5.2 Database Changes

Add `status` enum column to `workflows` table:
- `draft` (default)
- `live`
- `paused`
- `archived`

Replace `is_active` boolean. Migration converts: `is_active=true` → `live`, `is_active=false` → `draft`.

### 5.3 Publish Flow

1. User clicks "Publish" button
2. System validates all blocks are complete (have required config)
3. If incomplete blocks exist: highlight them with red border, show toast with count
4. If valid: set status to `live`, show success toast
5. For already-live workflows with edits: button says "Publish Changes"

---

## 6. Run History & Visualization

### 6.1 Run History Panel

Accessible via "Runs" button in top bar. Opens as a slide-over panel.

```
┌─────────────────────────────┐
│ Runs History           [✕]  │
├─────────────────────────────┤
│ ● Completed  Mar 1, 10:23  │  ← Green dot
│ ● Failed     Mar 1, 09:15  │  ← Red dot
│ ● Running    Mar 1, 08:45  │  ← Blue pulsing dot
│ ○ Cancelled  Feb 28, 16:30 │  ← Grey dot
│ ...                         │
└─────────────────────────────┘
```

### 6.2 Run Visualization

Selecting a run switches the canvas to **read-only run view**:

- Each block shows execution status badge (✓ completed, ✕ failed, ○ skipped)
- Failed blocks highlighted in red
- Clicking a block shows its **execution details** in the right panel:
  - Inputs at execution time
  - Outputs produced
  - Error message (if failed)
  - Duration
- Condition/Switch blocks show which path was taken (active path highlighted, other paths greyed)
- "Exit run view" button returns to the editor

---

## 7. Canvas Enhancements

### 7.1 Auto-Organize

Button in bottom toolbar. Applies a top-down hierarchical layout algorithm:
- Trigger at top
- Actions flow downward
- Branches spread horizontally
- Even spacing between blocks

Uses X6's built-in `dagre` layout plugin.

### 7.2 Canvas Notes

- Sticky-note style annotations (colored backgrounds)
- Created via note button in toolbar
- Free-positioned on canvas
- Don't affect workflow execution

### 7.3 Connection Lines

- **Blue solid**: Valid complete connection
- **Grey dashed**: Incomplete or disconnected
- Manhattan routing with rounded corners (keep current)
- Animated flow direction indicator on hover

### 7.4 Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| V | Pointer mode |
| H | Drag/pan mode |
| Ctrl+Z | Undo |
| Ctrl+Y | Redo |
| Ctrl+C | Copy selected |
| Ctrl+V | Paste |
| Delete | Delete selected (with confirmation for triggers) |
| Ctrl+S | Save |
| Ctrl+0 | Reset zoom |
| Ctrl++ | Zoom in |
| Ctrl+- | Zoom out |

---

## 8. Backend Changes Required (Phase 1 — UI Support Only)

### 8.1 Workflow Model

- Add `status` enum (draft/live/paused/archived), replace `is_active`
- Add `published_at` timestamp

### 8.2 Block Output Schema

Each action type declares its outputs (not just config schema):

```php
interface WorkflowAction {
    execute(config, context): array;
    static label(): string;
    static configSchema(): array;
    static outputSchema(): array; // NEW — declares outputs
}
```

This enables the variable picker to show available variables from upstream blocks.

### 8.3 Canvas API Changes

- `GET /canvas` response includes `outputSchema` for each block type so the frontend knows what variables each block produces
- `PUT /canvas` accepts block positions and configurations (no change needed)

### 8.4 Run API

- `GET /workflows/{id}/runs` — list runs with status, timestamps
- `GET /workflow-runs/{id}` — single run with step details including inputs/outputs

---

## 9. File Structure Changes

```
packages/workflow/
├── resources/
│   ├── js/workflow-builder/
│   │   ├── index.js              # Entry point (refactored)
│   │   ├── graph.js              # X6 graph setup (enhanced)
│   │   ├── alpine/               # NEW — Alpine.js components
│   │   │   ├── config-panel.js   # Right panel (replaces old config-panel.js)
│   │   │   ├── variable-picker.js # {x} variable picker
│   │   │   ├── block-picker.js   # + button popover
│   │   │   ├── top-bar.js        # Top bar state
│   │   │   ├── run-history.js    # Run history panel
│   │   │   └── run-viewer.js     # Run visualization
│   │   ├── nodes/                # X6 node shapes (redesigned)
│   │   │   ├── BaseNode.js       # NEW — shared node rendering
│   │   │   ├── TriggerNode.js    # Redesigned
│   │   │   ├── ActionNode.js     # Redesigned
│   │   │   ├── ConditionNode.js  # Redesigned
│   │   │   ├── FilterNode.js     # NEW — gate block
│   │   │   ├── SwitchNode.js     # NEW — multi-path
│   │   │   ├── DelayNode.js      # Redesigned
│   │   │   ├── LoopNode.js       # Redesigned
│   │   │   └── StopNode.js       # Redesigned
│   │   ├── toolbar.js            # Bottom toolbar (redesigned)
│   │   └── sidebar.js            # REMOVED (replaced by block-picker)
│   ├── css/
│   │   └── workflow-builder.css  # Complete rewrite
│   └── views/
│       └── builder.blade.php     # Rewritten layout
```

---

## 10. Migration Path

1. Keep all existing backend models and engine working
2. Build new UI alongside (don't break existing builder during development)
3. Switch over when new UI is feature-complete
4. Migrate `is_active` → `status` column
5. Phase 2 will add new backend actions (Record CRUD, Filter, Switch, etc.)

---

## 11. Out of Scope (Phase 2+)

- Record CRUD actions (Create, Update, Find)
- Filter block (gate)
- Switch block (multi-branch)
- AI actions
- Slack integration
- Templates/presets
- Credit system
- Sequences
- Delay Until action
- Calculation actions (Formula, Aggregate, etc.)
- Round Robin
- Canvas notes (deferred to keep Phase 1 focused)
