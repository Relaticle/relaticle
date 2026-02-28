import { Graph } from '@antv/x6';
import { Selection } from '@antv/x6-plugin-selection';
import { Snapline } from '@antv/x6-plugin-snapline';
import { Keyboard } from '@antv/x6-plugin-keyboard';
import { Clipboard } from '@antv/x6-plugin-clipboard';
import { History } from '@antv/x6-plugin-history';

function showConfirmDialog(message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'workflow-confirm-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'workflow-confirm-dialog';
    dialog.innerHTML = `
        <p>${message}</p>
        <div class="workflow-confirm-actions">
            <button class="workflow-confirm-cancel">Cancel</button>
            <button class="workflow-confirm-delete">Delete</button>
        </div>
    `;

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);

    dialog.querySelector('.workflow-confirm-cancel').onclick = () => overlay.remove();
    dialog.querySelector('.workflow-confirm-delete').onclick = () => {
        onConfirm();
        overlay.remove();
    };
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
}

export function createGraph(container, minimapContainer) {
    const graph = new Graph({
        container,
        width: container.offsetWidth,
        height: container.offsetHeight,
        grid: {
            visible: true,
            size: 10,
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
                            stroke: '#A2B1C3',
                            strokeWidth: 2,
                            targetMarker: {
                                name: 'block',
                                width: 12,
                                height: 8,
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
    graph.use(
        new Selection({
            enabled: true,
            multiple: true,
            rubberband: true,
        })
    );
    graph.use(new Snapline({ enabled: true }));
    graph.use(new Keyboard({ enabled: true }));
    graph.use(new Clipboard({ enabled: true }));
    graph.use(new History({ enabled: true }));

    // Keyboard shortcuts
    graph.bindKey(['meta+z', 'ctrl+z'], () => {
        graph.undo();
        return false;
    });
    graph.bindKey(['meta+shift+z', 'ctrl+y'], () => {
        graph.redo();
        return false;
    });
    graph.bindKey(['meta+c', 'ctrl+c'], () => {
        const cells = graph.getSelectedCells();
        if (cells.length) {
            graph.copy(cells);
        }
        return false;
    });
    graph.bindKey(['meta+v', 'ctrl+v'], () => {
        graph.paste({ offset: 32 });
        return false;
    });
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

    // Resize graph when container resizes
    const resizeObserver = new ResizeObserver(() => {
        graph.resize(container.offsetWidth, container.offsetHeight);
    });
    resizeObserver.observe(container);

    return graph;
}
