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
        },
        connecting: {
            router: 'manhattan',
            connector: {
                name: 'rounded',
                args: { radius: 8 },
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
                            stroke: '#94a3b8',
                            strokeWidth: 1.5,
                            targetMarker: {
                                name: 'block',
                                width: 10,
                                height: 6,
                            },
                        },
                    },
                    zIndex: 0,
                });
            },
            validateConnection({ sourceCell, targetCell }) {
                return sourceCell !== targetCell;
            },
        },
        panning: {
            enabled: true,
            eventTypes: ['leftMouseDown', 'mouseWheel'],
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
}

/**
 * Exit run view mode: restore editing, clear status overlays.
 */
export function exitRunView(graph) {
    graph.enableSelection();
    graph.enableKeyboard();

    graph.getNodes().forEach(node => {
        const data = node.getData() || {};
        const { _runStatus, ...rest } = data;
        node.setData(rest, { overwrite: true });
    });
}
