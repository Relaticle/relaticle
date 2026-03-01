# Troubleshooting Workflows (Attio Reference)

Source: https://attio.com/help/reference/automations/workflows/troubleshooting-workflows

## Accessing the Run Viewer

Navigate to a workflow and click the "Runs" button in the top left to view all executions for live or paused workflows, then select specific runs for detailed review.

## Run Viewer Overview

"The run viewer is divided into two main sections: a read-only view of the workflow on the left and a sidebar on the right that provides high-level statistics about the workflow and a list of all its runs."

### Key UX Pattern
- Left side: read-only workflow canvas with status overlays
- Right side: sidebar with run list and statistics

## Viewing a Specific Run

- Runs display executed blocks in sequence
- Click blocks to see inputs, outputs, and configuration details
- Failed blocks appear highlighted in **red** with error messages
- "Failed block executions do not count towards credit usage"

### Block Status Colors
- Green: completed successfully
- Red: failed with error
- Gray: skipped/not reached

## Canceling a Run

- Executing runs can be stopped via a red "Cancel run" button
- Prevents further block execution
- "The currently executing step may still complete, and if it does it will count towards credits usage"

## Loop Block Navigation

- Loop blocks show `<` and `>` buttons to navigate iterations
- Each iteration can be inspected individually

## Images

- `images/highlighted-workflow-row.png` — Workflow row in list view
- `images/view-runs.png` — Run viewer overview
- `images/view-run-example.png` — Specific run view with block statuses
- `images/run-sidebar-highlighted-block.png` — Run sidebar with highlighted block details
- `images/research-record-outputs.png` — Research record block outputs
