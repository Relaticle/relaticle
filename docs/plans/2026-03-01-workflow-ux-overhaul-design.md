# Workflow Builder Full UI/UX Overhaul — Design Document

**Date**: 2026-03-01
**Reference**: Attio workflow product (`attio-workflow/` in project root)
**Approach**: Layer-by-layer (visual foundation → functional core → polish)

---

## Layer 1: Visual Foundation

### 1a. Enhanced Node Cards
- Add description/subtitle below header bar showing configured summary (e.g., recipient for Send Email, condition expression for If/Else)
- Increase card width from 240px to 260px
- Add subtle drop shadow on hover (`0 2px 8px rgba(0,0,0,0.08)`)
- Enlarge ports on hover with glow effect; color condition ports (green=yes, red=no)

### 1b. Color-Coded Connection Labels
- "Yes"/"No" condition branch labels rendered as colored pills (green/red backgrounds)
- Edge paths from condition nodes colored to match their branch (green line for yes, red for no)

### 1c. Empty Canvas Onboarding
- Centered prompt when no nodes exist: icon + "Start building your workflow" heading + instructional subtext + primary "Add Trigger" button
- Disappears when first node is added

### 1d. Block Picker Improvements
- Add short description to each block (e.g., "Send an email notification")
- Wider popover (300px) to fit descriptions
- Category headers with left border accent in category color

### 1e. Loading & Empty States
- Run history: spinner during loading, helpful empty message when no runs
- Config panel: smooth transitions when switching nodes

---

## Layer 2: Functional Core

### 2a. Wire Up Variable Picker
- Add `{x}` button to every text/textarea input in `buildConfigHTML()`
- Click opens existing variable picker popover next to input
- Select inserts `{{source.key}}` at cursor
- Variables shown as highlighted `{{...}}` syntax
- Variable list grouped by upstream source node

### 2b. Unsaved Changes Indicator
- Track dirty state on cell add/remove/change, node data change, edge change
- Reset on save
- Visual indicator: dot or "Unsaved changes" text on/near Save button
- `beforeunload` browser warning when dirty
- Livewire `wire:navigate` interception prompt

### 2c. Config Panel Improvements
- Show action type icon + description in panel header (not just generic "Action Settings")
- Condition node: field selector dropdown from upstream variables (not free-text)
- Action type select grouped by category
- Add "Add a description..." editable field per node (stored in config, shown on canvas card)

### 2d. + Connector Buttons on Edges
- Hovering over an edge shows a small circular `+` button at midpoint
- Click opens block picker with source node context
- New node inserted between source and target, edges reconnected

---

## Layer 3: Polish Features

### 3a. Settings Panel
- Replace stub with real implementation:
  - Description: editable text area
  - Trigger config summary: read-only display
  - Failure notifications toggle
  - Danger zone: archive/delete with confirmation

### 3b. Run Viewer Improvements
- Human-readable block names (look up from canvas node data) instead of raw IDs
- Loading spinner during fetch
- Color-coded run status icons (green=completed, red=failed, gray=cancelled, blue=running)
- Run duration and trigger time display
- Step-by-step detail with expandable inputs/outputs
- Cancel button for in-progress runs

### 3c. Top Bar Refinements
- Tab-style navigation: "Editor" | "Runs (N)" | "Settings" — underlined tabs instead of separate buttons
- Favorite/star toggle (persisted to DB)
- Consistent status badge styling next to name

### 3d. Workflow List Page Improvements
- Mini canvas preview thumbnails (card/grid view)
- "Created by" column with avatar
- Grouping options (by status, trigger type)
- Workflow description as subtitle in list rows

### 3e. Micro-Interactions
- Block picker: scale + fade animation on open/close
- Node addition: subtle scale-up animation
- Toast notifications: progress bar for auto-dismiss timing
- Keyboard shortcut hints in toolbar tooltips (already partially done, ensure consistency)

---

## Files Affected

### JavaScript (`packages/workflow/resources/js/workflow-builder/`)
- `index.js` — dirty state tracking, unsaved changes, event listeners
- `graph.js` — edge hover + button, port hover styling, edge label styling
- `config-panel.js` — variable picker wiring, field selectors, descriptions
- `alpine/block-picker.js` — descriptions, wider popover, category styling
- `alpine/top-bar.js` — tab navigation, favorite toggle
- `alpine/run-history.js` — loading states, human-readable names, cancel run
- `alpine/variable-picker.js` — already implemented, needs wiring
- `toolbar.js` — no changes expected
- `nodes/TriggerNode.js`, `ActionNode.js`, `ConditionNode.js`, etc. — description text, wider cards, port styling

### CSS (`packages/workflow/resources/css/workflow-builder.css`)
- Node card styles (width, shadow, description)
- Port hover effects
- Edge label styling (colored pills)
- Empty canvas overlay
- Block picker (wider, descriptions, category borders)
- Tab navigation styles
- Loading spinner
- Micro-interaction transitions

### Blade (`packages/workflow/resources/views/builder.blade.php`)
- Empty canvas overlay HTML
- Tab navigation structure
- Settings panel content
- Loading state indicators

### PHP (backend)
- `WorkflowResource` — list page columns, grouping, preview
- Workflow model — `is_favorite` field, `description` field if not present
- Migration — add `is_favorite` boolean if needed
- API routes — favorite toggle endpoint, cancel run endpoint

---

## Attio Design Principles to Follow

1. **Tab-based navigation** in builder: Editor | Runs | Settings
2. **Inline status** everywhere with color-coded badges
3. **Progressive disclosure** — simple canvas, detail in sidebars on selection
4. **Descriptions on everything** — blocks, workflows, steps
5. **Visual flow** — clean connectors with labeled, colored branches
6. **Run history integrated** as a tab, not a separate page
7. **Block categories** with colored icons for instant recognition
8. **Minimal chrome** — few buttons, most interactions through the canvas
