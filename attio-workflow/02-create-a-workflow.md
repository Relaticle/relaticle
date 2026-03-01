# Create a Workflow (Attio Reference)

Source: https://attio.com/help/reference/automations/workflows/create-a-workflow

## Navigate the Workflows Canvas

The workflows canvas is the workspace where users add and order trigger and action blocks.

### Navigation Controls
- **Pointer mode** (default) — activated with `V` key
- **Drag/Pan mode** — activated with `H` key
- **Zoom controls** — magnifying glass icon or `Command` + scroll

## Build a Workflow

### Steps
1. Create a new workflow via "Automations" > "Workflows" > "Create workflow"
2. Choose between pre-built templates or starting from scratch
3. Select a trigger (the condition that initiates the workflow)
4. Define inputs to specify when the workflow runs
5. Add action blocks to define what the workflow does

### Action Blocks Can
- Apply logic such as filtering, if/else statements, and delays
- Perform calculations on attributes and records
- Create and modify records in Attio
- Send data to other applications via integrations

## Variables and Inputs

- Manually enter values or use the `{x}` icon to access variables from previous blocks
- **Yellow-highlighted** variables = missing fallback values
- **Blue-highlighted** variables = fallbacks are set

## Managing Blocks

- **Disconnect blocks**: Click arrows/connectors and press delete
- **Organize blocks**: Click "Organize blocks" button for auto-layout
- **Delete blocks**: Select individual or multiple blocks and delete
- **Add notes**: Annotative notes can be added to the canvas for documentation

## Publish a Workflow

- Click "Publish workflow" to set it live
- Changes to published workflows require clicking "Publish changes"
- Workflows can be paused via the toggle next to "Live"

## Key UX Patterns

- The `+` icon between blocks is the primary way to add new blocks
- Right panel shows configuration when a block is selected
- Canvas supports drag-to-pan, zoom, and keyboard shortcuts
- Blocks are connected by paths/arrows that show flow direction
