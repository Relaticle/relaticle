# Attio Workflow UI/UX Design Patterns Analysis

## Builder Canvas Layout

### Top Bar
- **Left**: Back breadcrumb ("Workflows > Workflow Name"), star/favorite icon
- **Center**: Tab navigation — **Editor** | **Runs** (with count badge) | **Settings**
- **Right**: Status indicator + Publish/Pause controls

### Canvas
- Clean white background with subtle dot grid
- Blocks are cards with rounded corners, clear visual hierarchy
- Each block shows: category tag (top-right), icon + name, description text
- Status badges on blocks during run view (green "Triggered", "Complete"; red "Failed")
- Connectors are simple lines with small circles at connection points
- Condition branches labeled directly on paths ("does match" / "does not match")

### Block Design (Attio style)
- **Header area**: Category tag (colored pill), block type icon (colored square with icon)
- **Body**: Block name (bold), description text below
- **Connection points**: Small circles at top/bottom of blocks
- **Status overlay in run view**: Colored badges ("Triggered", "Complete", "Failed")

### Right Sidebar (when block selected or in run view)
- Block icon + category + name at top
- "Change" button to swap block type
- "Add a description..." placeholder for documentation
- **Inputs section**: Form fields for configuration
- **Outputs section**: Shows block results during run view
- **Next step section**: Shows connected downstream blocks

## Workflow List Page

### Layout
- **Top toolbar**: Search, "View settings" dropdown, "New workflow" button (primary color)
- **Card view**: Workflow preview cards with mini canvas thumbnails, name, status badge
- **List view below**: Table with columns — Workflow, Runs, Status, Created by, Last published
- **Favorite star** icon on each row (hover to show tooltip "Add to favorites")
- **Three-dot menu** on each row for actions

### Sort/Filter/Group
- Sort by: Creation date, Last published, Number of runs, Name (with Ascending/Descending)
- Filter by: Creator (with avatar)
- Group by: Creation date, Status, Last published, Creator, Trigger, None
- Active sort/filter shown as pills at top of list

### Status Colors
- **Live**: Green pill
- **Paused**: Gray/neutral
- **Archived**: Gray text, muted appearance

## Run Viewer (Troubleshooting)

### Layout
- **Tab**: "Runs" tab with run count badge, underline active
- **Left**: Read-only canvas with block status overlays
- **Right sidebar**:
  - Top: Status summary ("Completed" badge + count)
  - Below: Scrollable list of runs ("Run #66", "Run #65", etc.) with green/red status dots
  - Each run clickable to load that specific run view

### Run Detail View
- Dark tooltip-style popover on selected block showing: Status, Runtime, Triggered time, Completed time, Credits used
- Green connection paths for completed flow
- Red highlighted blocks for failures

### Block Detail in Run View (right sidebar)
- Block icon + category + name
- **Run details**: Block status, Runtime, Executions, Credits used
- **Inputs**: Shows configured inputs for that execution
- **Outputs**: Shows actual output data
- **Next step**: Shows flow continuation

## Settings Tab
- Simple form layout
- **Quotas section**: "Maximum credits used per run" with number input
- **Notifications section**: Toggle for "Get notified when this workflow fails"
- Clean spacing, minimal UI

## Key Design Principles Observed

1. **Tab-based navigation** in builder: Editor | Runs | Settings (not separate pages)
2. **Inline status** everywhere — color-coded badges on blocks, rows, headers
3. **Progressive disclosure** — simple canvas, detail in sidebars on selection
4. **Descriptions on everything** — blocks, workflows all have "Add a description" placeholders
5. **Visual flow** — clean connectors with labeled branches
6. **Favorites** — star icon for quick access from list
7. **Run history integrated** — not a separate page, but a tab within the builder
8. **Block categories** with colored icons — makes block types instantly recognizable
9. **Minimal chrome** — very few buttons, most interactions through the canvas
10. **Consistent card design** — blocks are cards with header, body, connection points
