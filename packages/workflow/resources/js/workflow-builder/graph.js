import { Graph } from '@antv/x6';
import { Selection } from '@antv/x6-plugin-selection';
import { Snapline } from '@antv/x6-plugin-snapline';
import { Keyboard } from '@antv/x6-plugin-keyboard';
import { Clipboard } from '@antv/x6-plugin-clipboard';
import { History } from '@antv/x6-plugin-history';

/**
 * Show a confirmation dialog overlay.
 */
function showConfirmDialog(message, onConfirm) {
    const overlay = document.getElementById('confirm-dialog');
    if (overlay) {
        // Use the blade template's built-in dialog
        const msgEl = document.getElementById('confirm-message');
        const cancelBtn = document.getElementById('confirm-cancel');
        const okBtn = document.getElementById('confirm-ok');

        if (msgEl) msgEl.textContent = message;
        overlay.style.display = '';

        const cleanup = () => { overlay.style.display = 'none'; };

        cancelBtn.onclick = cleanup;
        okBtn.onclick = () => { onConfirm(); cleanup(); };
        overlay.onclick = (e) => { if (e.target === overlay) cleanup(); };
        return;
    }

    // Fallback: create overlay dynamically
    const el = document.createElement('div');
    el.className = 'wf-confirm-overlay';
    el.innerHTML = `
        <div class="wf-confirm-dialog">
            <p>${message}</p>
            <div class="wf-confirm-actions">
                <button class="wf-btn-secondary wf-confirm-cancel">Cancel</button>
                <button class="wf-btn-danger wf-confirm-delete">Delete</button>
            </div>
        </div>
    `;
    document.body.appendChild(el);
    el.querySelector('.wf-confirm-cancel').onclick = () => el.remove();
    el.querySelector('.wf-confirm-delete').onclick = () => { onConfirm(); el.remove(); };
    el.onclick = (e) => { if (e.target === el) el.remove(); };
}

/**
 * Check if connecting source -> target would create a cycle.
 * Does a DFS from target following outgoing edges to see if source is reachable.
 */
function wouldCreateCycle(graph, sourceId, targetId) {
    const visited = new Set();
    const stack = [targetId];

    while (stack.length > 0) {
        const nodeId = stack.pop();
        if (nodeId === sourceId) return true;
        if (visited.has(nodeId)) continue;
        visited.add(nodeId);

        const cell = graph.getCellById(nodeId);
        if (!cell) continue;

        const outEdges = graph.getConnectedEdges(cell, { outgoing: true });
        for (const edge of outEdges) {
            const target = edge.getTargetCellId();
            if (target && !visited.has(target)) {
                stack.push(target);
            }
        }
    }

    return false;
}

/**
 * Create and configure the X6 graph instance.
 */
export function createGraph(container, minimapContainer) {
    const graph = new Graph({
        container,
        width: container.offsetWidth,
        height: container.offsetHeight,
        grid: {
            visible: true,
            size: 20,
            type: 'dot',
            args: {
                color: '#e2e8f0',
                thickness: 1,
            },
        },
        connecting: {
            router: {
                name: 'manhattan',
                args: {
                    padding: 10,
                    step: 10,
                    startDirections: ['bottom'],
                    endDirections: ['top'],
                    excludeTerminals: ['source', 'target'],
                },
            },
            connector: {
                name: 'rounded',
                args: { radius: 10 },
            },
            anchor: 'center',
            connectionPoint: 'anchor',
            allowBlank: false,
            allowLoop: false,
            allowMulti: true,
            snap: { radius: 30 },
            createEdge() {
                return graph.createEdge({
                    attrs: {
                        line: {
                            stroke: '#cbd5e1',
                            strokeWidth: 1.5,
                            targetMarker: {
                                name: 'block',
                                width: 8,
                                height: 5,
                                fill: '#cbd5e1',
                            },
                        },
                    },
                    zIndex: 0,
                });
            },
            validateConnection({ sourceCell, targetCell, sourcePort, targetPort }) {
                if (sourceCell === targetCell) return false;

                const manifest = window.__wfManifest || {};
                const blockRules = manifest.blocks || {};

                // Get source and target node types
                const sourceData = sourceCell.getData() || {};
                const targetData = targetCell.getData() || {};
                const sourceType = sourceData.type;
                const targetType = targetData.type;

                if (!sourceType || !targetType) return true; // fallback: allow

                const rules = blockRules[sourceType];
                if (!rules) return true;

                // Check allowedTargets
                if (!rules.allowedTargets.includes(targetType)) {
                    return false;
                }

                // Check maxOutgoing
                if (rules.maxOutgoing !== null && rules.maxOutgoing !== undefined) {
                    const existingOutgoing = graph.getConnectedEdges(sourceCell, { outgoing: true });
                    if (existingOutgoing.length >= rules.maxOutgoing) {
                        return false;
                    }
                }

                // Cycle detection: DFS from target to see if source is reachable
                if (wouldCreateCycle(graph, sourceCell.id, targetCell.id)) {
                    return false;
                }

                return true;
            },
        },
        panning: {
            enabled: true,
            eventTypes: ['rightMouseDown', 'mouseWheel'],
        },
        mousewheel: {
            enabled: true,
            zoomAtMousePosition: true,
            modifiers: 'ctrl',
            minScale: 0.5,
            maxScale: 3,
        },
    });

    // Plugins
    graph.use(new Selection({ enabled: true, multiple: true, rubberband: true }));
    graph.use(new Snapline({ enabled: true }));
    graph.use(new Keyboard({ enabled: true }));
    graph.use(new Clipboard({ enabled: true }));
    graph.use(new History({ enabled: true }));

    // Keyboard shortcuts
    graph.bindKey(['meta+z', 'ctrl+z'], () => { graph.undo(); return false; });
    graph.bindKey(['meta+shift+z', 'ctrl+y'], () => { graph.redo(); return false; });
    graph.bindKey(['meta+c', 'ctrl+c'], () => {
        const cells = graph.getSelectedCells();
        if (cells.length) graph.copy(cells);
        return false;
    });
    graph.bindKey(['meta+v', 'ctrl+v'], () => { graph.paste({ offset: 32 }); return false; });
    graph.bindKey(['backspace', 'delete'], () => {
        const cells = graph.getSelectedCells();
        if (cells.length === 0) return false;
        const hasTrigger = cells.some(c => c.isNode() && c.getData()?.type === 'trigger');
        const message = hasTrigger
            ? 'This will delete the trigger node. The workflow will not function without it. Continue?'
            : `Delete ${cells.length} selected element(s)?`;
        showConfirmDialog(message, () => graph.removeCells(cells));
        return false;
    });

    // Ctrl+S for save
    graph.bindKey(['meta+s', 'ctrl+s'], () => {
        window.dispatchEvent(new CustomEvent('wf:save-requested'));
        return false;
    });

    // V/H mode toggle shortcuts
    graph.bindKey('v', () => {
        window.dispatchEvent(new CustomEvent('wf:set-mode', { detail: 'pointer' }));
        return false;
    });
    graph.bindKey('h', () => {
        window.dispatchEvent(new CustomEvent('wf:set-mode', { detail: 'hand' }));
        return false;
    });

    // Dispatch node selection events for Alpine
    graph.on('node:click', ({ node }) => {
        window.dispatchEvent(new CustomEvent('wf:node-selected', {
            detail: { nodeId: node.id, data: node.getData() },
        }));
    });

    graph.on('blank:click', () => {
        window.dispatchEvent(new CustomEvent('wf:node-deselected'));
    });

    graph.on('blank:dblclick', ({ e }) => {
        window.dispatchEvent(new CustomEvent('wf:open-picker', {
            detail: { x: e.clientX, y: e.clientY },
        }));
    });

    // Color-code edges from condition nodes
    graph.on('edge:connected', ({ edge }) => {
        const sourceNode = edge.getSourceNode();
        if (!sourceNode) return;
        const data = sourceNode.getData();
        if (data?.type !== 'condition') return;

        const sourcePortId = edge.getSourcePortId();
        const isYes = sourcePortId === 'out-yes';
        const label = isYes ? 'does match' : 'does not match';

        edge.setLabels([{
            attrs: {
                label: { text: label, fill: '#94a3b8', fontSize: 11, fontWeight: 500 },
                rect: { ref: 'label', fill: '#fff', rx: 4, ry: 4, refWidth: '120%', refHeight: '140%', refX: '-10%', refY: '-20%', stroke: '#e2e8f0', strokeWidth: 1 },
            },
        }]);
        edge.attr('line/stroke', isYes ? '#22c55e' : '#ef4444');
    });

    // Show + button on edge hover
    graph.on('edge:mouseenter', ({ edge }) => {
        const edgeView = graph.findViewByCell(edge);
        if (!edgeView) return;

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

    // Resize graph when container resizes
    const resizeObserver = new ResizeObserver(() => {
        graph.resize(container.offsetWidth, container.offsetHeight);
    });
    resizeObserver.observe(container);

    return graph;
}

/**
 * Enter run view mode: make graph read-only, highlight node statuses.
 */
export function enterRunView(graph, runData) {
    // Disable editing
    graph.disableSelection();
    graph.disableKeyboard();

    // Store run steps for popover access
    graph._runViewSteps = runData.steps || [];
    graph._runViewMode = true;

    // Map step statuses to node IDs
    const stepMap = {};
    if (runData.steps) {
        runData.steps.forEach(step => {
            if (step.node_id) {
                stepMap[step.node_id] = step.status;
            }
        });
    }

    // Update node visuals
    graph.getNodes().forEach(node => {
        const data = node.getData() || {};
        const nodeId = data.nodeId || node.id;
        const status = stepMap[nodeId];

        if (status) {
            node.setData({ ...data, _runStatus: status }, { overwrite: true });
        } else {
            node.setData({ ...data, _runStatus: 'skipped' }, { overwrite: true });
        }
    });

    // Color edges based on execution path
    graph.getEdges().forEach(edge => {
        const sourceNode = edge.getSourceNode();
        const targetNode = edge.getTargetNode();
        if (!sourceNode || !targetNode) return;

        const sourceStatus = sourceNode.getData()?._runStatus;
        const targetStatus = targetNode.getData()?._runStatus;

        if (sourceStatus === 'completed' && targetStatus === 'completed') {
            edge.attr('line/stroke', '#22c55e');
            edge.attr('line/strokeWidth', 2.5);
        } else if (sourceStatus === 'completed' && targetStatus === 'failed') {
            edge.attr('line/stroke', '#ef4444');
            edge.attr('line/strokeWidth', 2.5);
        } else {
            edge.attr('line/stroke', '#d1d5db');
            edge.attr('line/strokeWidth', 1.5);
            edge.attr('line/strokeDasharray', '4 2');
        }
    });
}

/**
 * Exit run view mode: restore editing, clear status overlays.
 */
export function exitRunView(graph) {
    graph.enableSelection();
    graph.enableKeyboard();
    graph._runViewSteps = null;
    graph._runViewMode = false;

    // Clear node status badges
    graph.getNodes().forEach(node => {
        const data = node.getData() || {};
        const { _runStatus, ...rest } = data;
        node.setData(rest, { overwrite: true });
    });

    // Reset edge styling
    graph.getEdges().forEach(edge => {
        edge.attr('line/stroke', '#cbd5e1');
        edge.attr('line/strokeWidth', 1.5);
        edge.attr('line/strokeDasharray', null);
    });
}
