# Workflow Builder Full UI/UX Overhaul — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Polish the workflow builder to deliver an Attio-level UX — enhanced visuals, functional gaps closed, and micro-interactions that make the product feel professional.

**Architecture:** Layer-by-layer approach. Layer 1 (visual foundation) ships first and is independently valuable. Layer 2 (functional core) builds on the new visuals. Layer 3 (polish features) adds finishing touches. Each task is a commit. Reference: `attio-workflow/DESIGN-PATTERNS.md`.

**Tech Stack:** Alpine.js + AntV X6 graph library, vanilla JS for node rendering, custom CSS (no Tailwind classes — raw properties matching Tailwind palette), Laravel/Filament backend, Vite IIFE build.

**Build command:** `cd packages/workflow && npm run build`
**Test in browser:** Use `agent-browser` per CLAUDE.md instructions.

---

## Layer 1: Visual Foundation

### Task 1: Enhanced Node Cards — Wider Cards with Description Text

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/BaseNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/TriggerNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/ActionNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/ConditionNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/DelayNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/LoopNode.js`
- Modify: `packages/workflow/resources/js/workflow-builder/nodes/StopNode.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Update BaseNode.js `createNodeHTML` to support a description line**

In `BaseNode.js`, add an optional `description` field to the node card HTML, shown below the summary:

```js
export function createNodeHTML(data, options) {
    const { color, icon, label, summary, description } = options;
    const displaySummary = summary || 'Click to configure';
    const descHtml = description
        ? `<span class="wf-block-description">${description}</span>`
        : '';

    return `
        <div class="wf-block" style="--block-color: ${color}">
            <div class="wf-block-header">
                <span class="wf-block-icon">${icon}</span>
                <span class="wf-block-label">${label}</span>
            </div>
            <div class="wf-block-body">
                <span class="wf-block-summary">${displaySummary}</span>
                ${descHtml}
            </div>
        </div>
    `;
}
```

**Step 2: Update all node files to use width 260 and pass description from config**

In each `*Node.js` file, change `width: 240` → `width: 260`. Add `description: data.config?.description || ''` to the options passed to `createNodeHTML`. For ConditionNode, set `height: 88` (from 80). No other height changes needed — the CSS overflow will handle varying description lengths.

**Step 3: Add CSS for description text and wider cards**

Add to `workflow-builder.css` after `.wf-block-summary`:

```css
.wf-block-description {
    font-size: 11px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    margin-top: 2px;
    font-style: italic;
}

.dark .wf-block-description {
    color: #64748b;
}
```

**Step 4: Add hover shadow enhancement**

The `.wf-block:hover` rule already has `box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08)` — verify this is working. No CSS change needed if already present.

**Step 5: Build and verify**

```bash
cd packages/workflow && npm run build
```

Open the builder in the browser. Verify node cards are wider (260px), summaries display correctly, and hover shadows work.

**Step 6: Commit**

```bash
git add packages/workflow/resources/js/workflow-builder/nodes/ packages/workflow/resources/css/workflow-builder.css public/vendor/workflow/
git commit -m "feat(workflow): widen node cards and add description support"
```

---

### Task 2: Color-Coded Condition Branch Labels

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/graph.js`
- Modify: `packages/workflow/resources/js/workflow-builder/index.js` (loadCanvas edge rendering)
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Style condition edges with colored labels during edge creation**

In `graph.js`, the `createEdge()` in the `connecting` config creates default gray edges. This is fine — condition labels are applied after connection. The real fix is in `index.js` `loadCanvas()`.

**Step 2: Update edge label rendering in `loadCanvas()`**

In `index.js`, around line 282-298, where edges are loaded, add label styling based on condition_label:

```js
// In loadCanvas(), when adding edges:
data.edges?.forEach((edge) => {
    const label = edge.condition_label;
    let labelConfig = [];
    if (label) {
        const isYes = label.toLowerCase() === 'yes';
        labelConfig = [{
            attrs: {
                label: {
                    text: label,
                    fill: '#fff',
                    fontSize: 11,
                    fontWeight: 600,
                },
                rect: {
                    ref: 'label',
                    fill: isYes ? '#22c55e' : '#ef4444',
                    rx: 10,
                    ry: 10,
                    refWidth: '140%',
                    refHeight: '140%',
                    refX: '-20%',
                    refY: '-20%',
                },
            },
        }];
    }

    graph.addEdge({
        id: edge.edge_id,
        source: edge.source_node_id,
        target: edge.target_node_id,
        labels: labelConfig,
        attrs: {
            line: {
                stroke: label ? (label.toLowerCase() === 'yes' ? '#22c55e' : '#ef4444') : '#94a3b8',
                strokeWidth: 1.5,
                targetMarker: { name: 'block', width: 10, height: 6 },
            },
        },
    });
});
```

**Step 3: Also color new edges created from condition nodes**

In `graph.js`, after a new edge is connected, listen for `edge:connected` and check if source is a condition node:

```js
graph.on('edge:connected', ({ edge }) => {
    const sourceNode = edge.getSourceNode();
    if (!sourceNode) return;
    const data = sourceNode.getData();
    if (data?.type !== 'condition') return;

    const sourcePortId = edge.getSourcePortId();
    const isYes = sourcePortId === 'out-yes';
    const label = isYes ? 'Yes' : 'No';

    edge.setLabels([{
        attrs: {
            label: { text: label, fill: '#fff', fontSize: 11, fontWeight: 600 },
            rect: { ref: 'label', fill: isYes ? '#22c55e' : '#ef4444', rx: 10, ry: 10, refWidth: '140%', refHeight: '140%', refX: '-20%', refY: '-20%' },
        },
    }]);
    edge.attr('line/stroke', isYes ? '#22c55e' : '#ef4444');
});
```

**Step 4: Build and verify**

Verify in browser: condition edges show green "Yes" / red "No" pills on the paths, and the lines are colored accordingly.

**Step 5: Commit**

```bash
git add packages/workflow/resources/js/workflow-builder/ public/vendor/workflow/
git commit -m "feat(workflow): color-coded condition branch labels and edges"
```

---

### Task 3: Empty Canvas Onboarding

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add empty canvas overlay HTML to builder.blade.php**

Inside `.wf-canvas-area`, after `#workflow-canvas-container`, add:

```html
{{-- Empty Canvas Onboarding --}}
<div class="wf-empty-canvas" x-show="!hasNodes" x-transition>
    <div class="wf-empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    </div>
    <h2 class="wf-empty-title">Start building your workflow</h2>
    <p class="wf-empty-text">Double-click the canvas or use the + button to add your first block.</p>
    <button type="button" class="wf-empty-btn" @click="openBlockPicker(null, $el.parentElement.getBoundingClientRect().left + $el.parentElement.getBoundingClientRect().width / 2, $el.parentElement.getBoundingClientRect().top + $el.parentElement.getBoundingClientRect().height / 2)">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Trigger
    </button>
</div>
```

**Step 2: Add `hasNodes` computed property in index.js**

In the `workflowBuilderFactory`, add a reactive `hasNodes` property:

```js
hasNodes: false,
```

And update it after `loadCanvas` completes and on cell add/remove events:

```js
// In init(), after this.loadCanvas(graph):
graph.on('cell:added', () => { this.hasNodes = graph.getNodes().length > 0; });
graph.on('cell:removed', () => { this.hasNodes = graph.getNodes().length > 0; });
```

At the end of `loadCanvas()`, after loading nodes:
```js
this.hasNodes = graph.getNodes().length > 0;
```

**Step 3: Add CSS for empty canvas overlay**

```css
/* --- Empty Canvas Onboarding --- */
.wf-empty-canvas {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    pointer-events: none;
}

.wf-empty-canvas > * {
    pointer-events: auto;
}

.wf-empty-icon {
    color: #94a3b8;
    margin-bottom: 16px;
}

.dark .wf-empty-icon {
    color: #475569;
}

.wf-empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #334155;
    margin: 0 0 8px;
}

.dark .wf-empty-title {
    color: #e2e8f0;
}

.wf-empty-text {
    font-size: 14px;
    color: #94a3b8;
    margin: 0 0 20px;
}

.wf-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}

.wf-empty-btn:hover {
    background: #2563eb;
}
```

**Step 4: Build, verify, commit**

Open a new/empty workflow builder. Verify the onboarding prompt appears. Add a node — verify it disappears.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): add empty canvas onboarding prompt"
```

---

### Task 4: Block Picker — Descriptions and Wider Layout

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/block-picker.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add descriptions to each block in block-picker.js**

Update the `categories` array in `blockPickerData()` to include `description` for each block:

```js
categories: [
    {
        name: 'Triggers',
        blocks: [
            { type: 'trigger', label: 'Trigger', description: 'Start your workflow', icon: ICONS.trigger, color: COLORS.trigger },
        ],
    },
    {
        name: 'Actions',
        blocks: [
            { type: 'action', label: 'Send Email', description: 'Send an email notification', actionType: 'send_email', icon: ICONS.action, color: COLORS.action },
            { type: 'action', label: 'Send Webhook', description: 'Send data to an external URL', actionType: 'send_webhook', icon: ICONS.action, color: COLORS.action },
            { type: 'action', label: 'HTTP Request', description: 'Make an HTTP API call', actionType: 'http_request', icon: ICONS.action, color: COLORS.action },
        ],
    },
    {
        name: 'Logic',
        blocks: [
            { type: 'condition', label: 'If / Else', description: 'Branch based on a condition', icon: ICONS.condition, color: COLORS.condition },
        ],
    },
    {
        name: 'Timing',
        blocks: [
            { type: 'delay', label: 'Delay', description: 'Wait before continuing', icon: ICONS.delay, color: COLORS.delay },
        ],
    },
    {
        name: 'Flow',
        blocks: [
            { type: 'loop', label: 'Loop', description: 'Iterate over a collection', icon: ICONS.loop, color: COLORS.loop },
            { type: 'stop', label: 'Stop', description: 'End the workflow', icon: ICONS.stop, color: COLORS.stop },
        ],
    },
],
```

**Step 2: Update block picker HTML in builder.blade.php to show descriptions**

Replace the block picker item template:

```html
<button
    type="button"
    class="wf-picker-item"
    @click="addBlock(block)"
>
    <span class="wf-picker-icon" :style="'color:' + block.color" x-html="block.icon"></span>
    <span class="wf-picker-item-text">
        <span class="wf-picker-item-label" x-text="block.label"></span>
        <span class="wf-picker-item-desc" x-text="block.description"></span>
    </span>
</button>
```

**Step 3: Update CSS — wider picker, description styling, category accent**

```css
.wf-picker {
    /* Change width from 260px to 300px */
    width: 300px;
    max-height: 400px;
    /* rest stays the same */
}

.wf-picker-category-name {
    /* Add left border accent */
    border-left: 3px solid #e2e8f0;
    padding-left: 8px;
    margin-left: 4px;
}

.wf-picker-item-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.wf-picker-item-label {
    font-size: 13px;
    font-weight: 500;
}

.wf-picker-item-desc {
    font-size: 11px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dark .wf-picker-item-desc {
    color: #64748b;
}
```

**Step 4: Build, verify, commit**

Open the block picker. Verify it's wider, descriptions appear below each label, and category names have accent borders.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): add block descriptions and widen picker"
```

---

### Task 5: Loading & Empty States for Run History

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add loading spinner and improved empty state to runs panel in builder.blade.php**

Replace the runs panel body content with loading/empty states:

After `<button ... @click="loadRuns()">Refresh</button>`, add:

```html
{{-- Loading state --}}
<template x-if="loadingRuns">
    <div class="wf-loading">
        <div class="wf-spinner"></div>
        <span>Loading runs...</span>
    </div>
</template>
```

Update the empty state template:

```html
<template x-if="runs.length === 0 && !loadingRuns">
    <div class="wf-panel-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="wf-panel-empty-icon"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <p>No runs yet</p>
        <span>Publish and trigger your workflow to see results here.</span>
    </div>
</template>
```

**Step 2: Add CSS for loading spinner and empty state**

```css
/* --- Loading State --- */
.wf-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 32px 0;
    color: #94a3b8;
    font-size: 13px;
}

.wf-spinner {
    width: 24px;
    height: 24px;
    border: 2.5px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.dark .wf-spinner {
    border-color: #334155;
    border-top-color: #60a5fa;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* --- Panel Empty State --- */
.wf-panel-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 32px 16px;
    color: #94a3b8;
}

.wf-panel-empty-icon {
    margin-bottom: 12px;
    color: #cbd5e1;
}

.dark .wf-panel-empty-icon {
    color: #475569;
}

.wf-panel-empty p {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    margin: 0 0 4px;
}

.dark .wf-panel-empty p {
    color: #94a3b8;
}

.wf-panel-empty span {
    font-size: 12px;
    color: #94a3b8;
}
```

**Step 3: Build, verify, commit**

Open runs panel on a workflow with no runs — verify empty state with icon. On a workflow with runs, verify spinner shows briefly during load.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): add loading spinner and empty states for run history"
```

---

## Layer 2: Functional Core

### Task 6: Wire Up Variable Picker

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/config-panel.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Add `{x}` buttons to config panel inputs**

In `config-panel.js`, update `buildConfigHTML()` to wrap each text/textarea input in a position-relative container with a `{x}` button. Create a helper:

```js
_wrapWithVarButton(inputHtml, configKey) {
    return `
        <div style="position: relative;">
            ${inputHtml}
            <button type="button" class="wf-var-btn" data-var-for="${configKey}" title="Insert variable">{x}</button>
        </div>
    `;
},
```

Then in `buildConfigHTML`, for each text input and textarea, wrap with `this._wrapWithVarButton(...)`. Do NOT wrap select elements, number inputs, or disabled inputs.

**Step 2: Handle `{x}` button clicks in `bindFormHandlers()`**

Add click handlers for `.wf-var-btn` elements:

```js
container.querySelectorAll('.wf-var-btn').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const configKey = btn.dataset.varFor;
        const input = container.querySelector(`[data-config-key="${configKey}"]`);
        if (input && this.selectedNodeId) {
            this.openVariablePicker(input, this.selectedNodeId);
        }
    });
});
```

**Step 3: Add variable picker popover HTML to builder.blade.php**

After the block picker popover, add:

```html
{{-- Variable Picker Popover --}}
<div
    x-show="varPickerOpen"
    x-transition
    class="wf-var-picker"
    :style="'left:' + varPickerPos.x + 'px; top:' + varPickerPos.y + 'px'"
    @click.outside="closeVariablePicker()"
>
    <template x-for="group in variables" :key="group.nodeId">
        <div>
            <div class="wf-var-group-name" x-text="group.source"></div>
            <template x-for="output in group.outputs" :key="output.key">
                <button
                    type="button"
                    class="wf-var-item"
                    @click="insertVariable(group.nodeId, output.key)"
                >
                    <span class="wf-var-key" x-text="output.key"></span>
                    <span class="wf-var-label" x-text="output.label"></span>
                </button>
            </template>
        </div>
    </template>
    <template x-if="variables.length === 0">
        <p class="wf-panel-placeholder">No upstream variables available.</p>
    </template>
</div>
```

**Step 4: Build, verify, commit**

Open builder, select a node, verify `{x}` buttons appear on text inputs. Click one — verify the variable picker popover opens with upstream node variables grouped by source.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): wire up variable picker with {x} buttons on config inputs"
```

---

### Task 7: Unsaved Changes Indicator

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add dirty state tracking in index.js**

Add to the component state:

```js
isDirty: false,
```

In `init()`, after graph is created, track changes:

```js
// Track dirty state
const markDirty = () => { this.isDirty = true; };
graph.on('cell:added', markDirty);
graph.on('cell:removed', markDirty);
graph.on('cell:changed', markDirty);
graph.on('node:moved', markDirty);
graph.on('edge:connected', markDirty);
graph.on('edge:removed', markDirty);
```

Reset on save, in `saveCanvas()` after successful response:

```js
if (response.ok) {
    this.isDirty = false;
    showToast('Workflow saved successfully.', 'success');
}
```

**Step 2: Add beforeunload warning**

In `init()`:

```js
window.addEventListener('beforeunload', (e) => {
    if (this.isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});
```

**Step 3: Show visual indicator in builder.blade.php**

Update the Save button to show a dot when dirty:

```html
<button
    type="button"
    class="wf-save-btn"
    @click="saveCanvas()"
    :disabled="saving"
    :class="{ 'wf-save-dirty': isDirty }"
    id="save-btn"
>
    <svg ...></svg>
    <span x-text="saving ? 'Saving...' : 'Save'"></span>
</button>
```

**Step 4: Add CSS for dirty indicator**

```css
.wf-save-btn.wf-save-dirty {
    border-color: #f59e0b;
}

.wf-save-btn.wf-save-dirty::after {
    content: '';
    width: 6px;
    height: 6px;
    background: #f59e0b;
    border-radius: 50%;
    position: absolute;
    top: 4px;
    right: 4px;
}
```

Also add `position: relative` to `.wf-save-btn`.

**Step 5: Build, verify, commit**

Open builder, move a node — verify Save button gets amber border + dot. Save — verify it resets. Try to close tab with unsaved changes — verify browser warning.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): add unsaved changes indicator and browser warning"
```

---

### Task 8: Config Panel Improvements — Better Headers and Node Descriptions

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/config-panel.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Improve config panel header to show specific action type**

In `builder.blade.php`, update the config panel header `<h3>`:

```html
<h3>
    <span x-text="selectedNode?.type === 'action' && selectedNode?.actionType
        ? selectedNode.actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + ' Settings'
        : selectedNode?.type ? selectedNode.type.charAt(0).toUpperCase() + selectedNode.type.slice(1) + ' Settings' : 'Settings'">
    </span>
</h3>
```

**Step 2: Add "Add a description..." field at the top of every config form**

In `config-panel.js` `buildConfigHTML()`, right after the Type disabled input, add:

```js
html += `
    <div class="wf-config-group">
        <label>Description</label>
        <input type="text" data-config-key="description" value="${esc(data.config?.description || '')}" placeholder="Add a description..." class="wf-config-description">
    </div>
`;
```

This stores in `config.description` and gets rendered on the canvas card via BaseNode.js (already wired in Task 1).

**Step 3: Build, verify, commit**

Select an action node — verify header shows "Send Email Settings" instead of "Action Settings". Type a description — verify it appears on the canvas card.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): improve config panel headers and add node descriptions"
```

---

### Task 9: + Connector Buttons on Edges

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/graph.js`
- Modify: `packages/workflow/resources/js/workflow-builder/index.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add edge:mouseenter / edge:mouseleave handlers in graph.js**

After the `blank:dblclick` handler, add:

```js
// Show + button on edge hover
graph.on('edge:mouseenter', ({ edge, e }) => {
    const edgeView = graph.findViewByCell(edge);
    if (!edgeView) return;

    // Get the midpoint of the edge path
    const pathEl = edgeView.findOne('path[data-index="0"]');
    if (!pathEl) return;

    const totalLength = pathEl.getTotalLength();
    const midPoint = pathEl.getPointAtLength(totalLength / 2);
    const ctm = pathEl.getScreenCTM();
    if (!ctm) return;

    const screenX = midPoint.x * ctm.a + ctm.e;
    const screenY = midPoint.y * ctm.d + ctm.f;

    window.dispatchEvent(new CustomEvent('wf:show-edge-add', {
        detail: { edgeId: edge.id, x: screenX, y: screenY },
    }));
});

graph.on('edge:mouseleave', ({ edge }) => {
    window.dispatchEvent(new CustomEvent('wf:hide-edge-add', {
        detail: { edgeId: edge.id },
    }));
});
```

**Step 2: Add edge add button state and handler in index.js**

Add state:
```js
edgeAddBtn: { visible: false, x: 0, y: 0, edgeId: null },
```

Add listeners in `init()`:
```js
window.addEventListener('wf:show-edge-add', (e) => {
    this.edgeAddBtn = { visible: true, ...e.detail };
});
window.addEventListener('wf:hide-edge-add', () => {
    // Delay hide to allow clicking the button
    setTimeout(() => { if (!this._edgeAddHover) this.edgeAddBtn.visible = false; }, 200);
});
```

Add method to handle inserting a node between two nodes:
```js
insertBlockOnEdge(block) {
    const graph = window.__wfGraph;
    if (!graph || !this.edgeAddBtn.edgeId) return;

    const edge = graph.getCellById(this.edgeAddBtn.edgeId);
    if (!edge) return;

    const sourceId = edge.getSourceCellId();
    const targetId = edge.getTargetCellId();
    const sourcePortId = edge.getSourcePortId();
    const targetPortId = edge.getTargetPortId();

    // Remove old edge
    graph.removeCell(edge);

    // Add new node positioned between source and target
    const sourceNode = graph.getCellById(sourceId);
    const targetNode = graph.getCellById(targetId);
    let midX = 300, midY = 300;
    if (sourceNode && targetNode) {
        const sp = sourceNode.getPosition();
        const tp = targetNode.getPosition();
        midX = (sp.x + tp.x) / 2;
        midY = (sp.y + tp.y) / 2;
    }

    const pos = { x: midX, y: midY };
    const newNode = addBlockToGraph(graph, block, null, null, pos);
    if (!newNode) return;

    // Connect source → new node
    graph.addEdge({ source: { cell: sourceId, port: sourcePortId || 'out' }, target: { cell: newNode.id, port: 'in' } });
    // Connect new node → target
    graph.addEdge({ source: { cell: newNode.id, port: 'out' }, target: { cell: targetId, port: targetPortId || 'in' } });

    this.edgeAddBtn.visible = false;
    this.blockPickerOpen = false;
},
```

**Step 3: Add floating + button HTML in builder.blade.php**

After the block picker popover:
```html
{{-- Edge Add Button --}}
<button
    type="button"
    class="wf-edge-add-btn"
    x-show="edgeAddBtn.visible"
    :style="'left:' + edgeAddBtn.x + 'px; top:' + edgeAddBtn.y + 'px'"
    @mouseenter="_edgeAddHover = true"
    @mouseleave="_edgeAddHover = false; edgeAddBtn.visible = false"
    @click="openBlockPicker(null, edgeAddBtn.x, edgeAddBtn.y); _insertOnEdge = edgeAddBtn.edgeId"
    title="Insert block"
>
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
</button>
```

**Step 4: Add CSS for edge add button**

```css
.wf-edge-add-btn {
    position: fixed;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1.5px solid #3b82f6;
    border-radius: 50%;
    color: #3b82f6;
    cursor: pointer;
    z-index: 50;
    transform: translate(-50%, -50%);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.15s;
}

.wf-edge-add-btn:hover {
    background: #3b82f6;
    color: #fff;
    transform: translate(-50%, -50%) scale(1.15);
}

.dark .wf-edge-add-btn {
    background: #1e293b;
    border-color: #60a5fa;
    color: #60a5fa;
}
```

**Step 5: Update `addBlock()` in index.js to detect edge insertion mode**

Modify `addBlock()` to call `insertBlockOnEdge` when `_insertOnEdge` is set:

```js
addBlock(block) {
    const graph = window.__wfGraph;
    if (!graph) return;

    if (this._insertOnEdge) {
        this.edgeAddBtn.edgeId = this._insertOnEdge;
        this._insertOnEdge = null;
        this.insertBlockOnEdge(block);
        return;
    }

    const pos = !this.blockPickerSourceNode && this.blockPickerPos
        ? graph.pageToLocal(this.blockPickerPos.x, this.blockPickerPos.y)
        : null;
    addBlockToGraph(graph, block, this.blockPickerSourceNode, null, pos);
    this.blockPickerOpen = false;
},
```

**Step 6: Build, verify, commit**

Hover over an edge between two nodes — verify `+` button appears at midpoint. Click it — verify block picker opens. Select a block — verify it's inserted between the two nodes with edges reconnected.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): add + connector buttons to insert blocks on edges"
```

---

## Layer 3: Polish Features

### Task 10: Settings Panel Implementation

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/top-bar.js`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Replace settings panel stub in builder.blade.php**

Replace the settings panel body placeholder with:

```html
<div class="wf-panel-body">
    <div class="wf-settings-section">
        <h4>Description</h4>
        <textarea
            class="wf-settings-textarea"
            x-model="workflowDescription"
            @blur="saveDescription()"
            placeholder="Add a workflow description..."
            rows="3"
        ></textarea>
    </div>

    <div class="wf-settings-section">
        <h4>Trigger</h4>
        <div class="wf-settings-info">
            <span class="wf-settings-label">Type</span>
            <span class="wf-settings-value" x-text="triggerType || 'Not configured'"></span>
        </div>
    </div>

    <div class="wf-settings-section wf-settings-danger">
        <h4>Danger Zone</h4>
        <button
            type="button"
            class="wf-btn-sm wf-btn-danger-outline"
            @click="archiveWorkflow()"
            x-show="workflowStatus === 'live' || workflowStatus === 'paused'"
        >Archive Workflow</button>
    </div>
</div>
```

**Step 2: Add `workflowDescription`, `triggerType`, and `saveDescription()` to top-bar.js**

```js
workflowDescription: '',
triggerType: '',

async saveDescription() {
    if (!this.workflowId) return;
    try {
        await fetch(`/workflow/api/workflows/${this.workflowId}/canvas`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ description: this.workflowDescription }),
        });
    } catch (e) {
        console.warn('Failed to save description:', e);
    }
},
```

In `index.js` `loadCanvas()`, after loading meta, populate these:
```js
this.workflowDescription = data.meta?.description || '';
this.triggerType = data.meta?.trigger_type || '';
```

**Step 3: Add CSS for settings sections**

```css
.wf-settings-section {
    margin-bottom: 24px;
}

.wf-settings-section h4 {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 10px;
}

.dark .wf-settings-section h4 {
    color: #94a3b8;
}

.wf-settings-textarea {
    width: 100%;
    padding: 8px 10px;
    font-size: 13px;
    color: #1e293b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    outline: none;
    resize: vertical;
    font-family: inherit;
}

.wf-settings-textarea:focus {
    border-color: #3b82f6;
}

.dark .wf-settings-textarea {
    color: #e2e8f0;
    background: #0f172a;
    border-color: #334155;
}

.wf-settings-info {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
}

.wf-settings-label {
    color: #64748b;
}

.wf-settings-value {
    color: #1e293b;
    font-weight: 500;
}

.dark .wf-settings-value {
    color: #e2e8f0;
}

.wf-settings-danger {
    border-top: 1px solid #fee2e2;
    padding-top: 16px;
}

.wf-btn-danger-outline {
    color: #ef4444;
    border-color: #fecaca;
}

.wf-btn-danger-outline:hover {
    background: #fef2f2;
    color: #dc2626;
}
```

**Step 4: Build, verify, commit**

Open Settings panel. Verify description textarea, trigger type display, and danger zone archive button work.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): implement settings panel with description and danger zone"
```

---

### Task 11: Run Viewer Improvements — Human-Readable Names and Status Colors

**Files:**
- Modify: `packages/workflow/resources/js/workflow-builder/alpine/run-history.js`
- Modify: `packages/workflow/resources/views/builder.blade.php`

**Step 1: Add node name lookup to run-history.js**

Add a method to resolve node IDs to human-readable names:

```js
getNodeLabel(nodeId) {
    const graph = window.__wfGraph;
    if (!graph) return nodeId;
    const cell = graph.getCellById(nodeId);
    if (!cell) return nodeId;
    const data = cell.getData() || {};
    const type = data.type || 'unknown';
    const actionType = data.actionType;
    if (actionType) {
        return actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }
    return type.charAt(0).toUpperCase() + type.slice(1);
},
```

**Step 2: Update run steps display in builder.blade.php**

Replace `x-text="step.node_id || 'Unknown'"` with:

```html
<span x-text="$root.__x.$data.getNodeLabel ? $root.__x.$data.getNodeLabel(step.node_id) : (step.node_id || 'Unknown')"></span>
```

Actually, since `getNodeLabel` is in the `runHistory` component, use a simpler approach — change:
```html
<span x-text="step.node_id || 'Unknown'"></span>
```
to:
```html
<span x-text="getNodeLabel(step.node_id)"></span>
```

This works because `getNodeLabel` is in the `runHistory` Alpine scope.

**Step 3: Add run duration display**

In the run detail header area, add:

```html
<template x-if="selectedRun.completed_at && selectedRun.started_at">
    <span class="wf-run-duration" x-text="formatDuration(selectedRun.started_at, selectedRun.completed_at)"></span>
</template>
```

Add `formatDuration` to `run-history.js`:

```js
formatDuration(start, end) {
    if (!start || !end) return '';
    const ms = new Date(end) - new Date(start);
    if (ms < 1000) return `${ms}ms`;
    if (ms < 60000) return `${Math.round(ms / 1000)}s`;
    return `${Math.round(ms / 60000)}m`;
},
```

**Step 4: Build, verify, commit**

Open run history, select a run. Verify steps show "Send Email", "Condition", etc. instead of raw IDs. Verify duration displays.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): show human-readable names and duration in run viewer"
```

---

### Task 12: Top Bar Tab Navigation

**Files:**
- Modify: `packages/workflow/resources/views/builder.blade.php`
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Replace Runs/Settings buttons with tab-style navigation**

In builder.blade.php, replace the Runs and Settings buttons with:

```html
<div class="wf-topbar-tabs">
    <button
        type="button"
        class="wf-topbar-tab"
        :class="{ 'active': panelView !== 'runs' && panelView !== 'settings' }"
        @click="closePanel()"
    >Editor</button>
    <button
        type="button"
        class="wf-topbar-tab"
        :class="{ 'active': panelView === 'runs' }"
        @click="togglePanel('runs')"
    >Runs</button>
    <button
        type="button"
        class="wf-topbar-tab"
        :class="{ 'active': panelView === 'settings' }"
        @click="togglePanel('settings')"
    >Settings</button>
</div>
```

**Step 2: Add tab CSS**

```css
.wf-topbar-tabs {
    display: flex;
    align-items: center;
    gap: 0;
    height: 100%;
}

.wf-topbar-tab {
    padding: 0 14px;
    height: 100%;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}

.wf-topbar-tab:hover {
    color: #334155;
}

.wf-topbar-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.dark .wf-topbar-tab {
    color: #94a3b8;
}

.dark .wf-topbar-tab:hover {
    color: #e2e8f0;
}

.dark .wf-topbar-tab.active {
    color: #60a5fa;
    border-bottom-color: #60a5fa;
}
```

**Step 3: Build, verify, commit**

Verify tabs display "Editor | Runs | Settings" with underline active state matching Attio's pattern.

```bash
git add packages/workflow/resources/ public/vendor/workflow/
git commit -m "feat(workflow): replace panel buttons with tab-style navigation"
```

---

### Task 13: Workflow List Page Improvements

**Files:**
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource.php`
- Modify: `packages/workflow/src/Filament/Resources/WorkflowResource/Pages/ListWorkflows.php`

**Step 1: Add description column and creator column to WorkflowResource::table()**

In `WorkflowResource.php`, update the table columns:

```php
TextColumn::make('description')
    ->limit(50)
    ->toggleable()
    ->placeholder('No description'),
```

Add after `status` column. Also add grouping support in the table:

```php
->groups([
    'status',
    'trigger_type',
])
```

**Step 2: Build, verify, commit**

Navigate to workflow list page. Verify description column shows, and group-by options work.

```bash
git add packages/workflow/src/Filament/ public/vendor/workflow/
git commit -m "feat(workflow): add description column and grouping to workflow list"
```

---

### Task 14: Micro-Interactions — Animations and Transitions

**Files:**
- Modify: `packages/workflow/resources/css/workflow-builder.css`

**Step 1: Add block picker animation**

The block picker already has `x-transition` on Alpine. Enhance with CSS:

```css
.wf-picker[x-transition] {
    transition: opacity 0.15s, transform 0.15s;
}

.wf-picker[x-transition-enter] {
    opacity: 0;
    transform: scale(0.95);
}
```

**Step 2: Add toast progress bar**

Update `.wf-toast`:

```css
.wf-toast {
    /* existing styles... */
    overflow: hidden;
    position: relative;
}

.wf-toast::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    background: currentColor;
    opacity: 0.3;
    animation: toastProgress 3s linear forwards;
}

@keyframes toastProgress {
    from { width: 100%; }
    to { width: 0%; }
}
```

**Step 3: Add port hover enhancement**

```css
.x6-port-body:hover {
    r: 7;
    stroke-width: 2;
    filter: drop-shadow(0 0 3px rgba(59, 130, 246, 0.4));
}
```

Note: X6 ports are SVG — this may need to be handled via X6's port attrs instead. If CSS doesn't work on SVG ports, skip this sub-step.

**Step 4: Build, verify, commit**

Verify block picker has a subtle scale-in animation. Verify toast has a progress bar at the bottom.

```bash
git add packages/workflow/resources/css/ public/vendor/workflow/
git commit -m "feat(workflow): add micro-interactions and animations"
```

---

### Task 15: Final Build, Full E2E Verification, and Cleanup

**Step 1: Full build**

```bash
cd packages/workflow && npm run build
```

**Step 2: Browser verification checklist**

Use `agent-browser` to verify ALL of the following:

1. Open builder on a workflow with nodes:
   - [ ] Node cards are 260px wide with descriptions
   - [ ] Condition edges show colored "Yes"/"No" pills
   - [ ] Edge lines are green/red for condition branches
   - [ ] Bottom toolbar has + button, it opens block picker
   - [ ] Block picker is 300px wide with descriptions
   - [ ] Tab navigation shows Editor | Runs | Settings

2. Open builder on a new empty workflow:
   - [ ] Empty canvas onboarding prompt appears
   - [ ] Click "Add Trigger" opens block picker
   - [ ] Adding a node makes onboarding disappear

3. Select a node:
   - [ ] Config panel header shows specific action name
   - [ ] `{x}` buttons appear on text inputs
   - [ ] Variable picker opens and lists upstream variables
   - [ ] Description field present

4. Modify the canvas:
   - [ ] Save button shows amber dirty indicator
   - [ ] Saving resets the indicator
   - [ ] Browser warns on close with unsaved changes

5. Hover an edge:
   - [ ] `+` button appears at edge midpoint
   - [ ] Clicking opens block picker
   - [ ] Adding a block inserts between nodes

6. Open Settings tab:
   - [ ] Description textarea works
   - [ ] Trigger type shows
   - [ ] Archive button visible for live/paused

7. Open Runs tab:
   - [ ] Loading spinner shows briefly
   - [ ] Empty state shows for workflows with no runs
   - [ ] Run steps show human-readable names
   - [ ] Run duration displays

8. Workflow list page:
   - [ ] Description column present
   - [ ] Group-by options work

**Step 3: Commit final state**

```bash
git add -A
git commit -m "feat(workflow): complete UI/UX overhaul — Attio-level polish"
```
