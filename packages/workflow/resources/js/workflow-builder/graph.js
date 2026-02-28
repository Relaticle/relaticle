import { Graph } from '@antv/x6';
import { Selection } from '@antv/x6-plugin-selection';
import { Snapline } from '@antv/x6-plugin-snapline';
import { Keyboard } from '@antv/x6-plugin-keyboard';
import { Clipboard } from '@antv/x6-plugin-clipboard';
import { History } from '@antv/x6-plugin-history';

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
        if (cells.length) {
            graph.removeCells(cells);
        }
        return false;
    });

    // Resize graph when container resizes
    const resizeObserver = new ResizeObserver(() => {
        graph.resize(container.offsetWidth, container.offsetHeight);
    });
    resizeObserver.observe(container);

    return graph;
}
