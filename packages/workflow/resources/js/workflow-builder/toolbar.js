/**
 * Toolbar helper functions for the workflow builder.
 *
 * The toolbar UI is now in the blade template with Alpine bindings.
 * These functions provide the implementations for toolbar actions.
 */

/**
 * Attempt dagre auto-layout on the graph.
 * Falls back gracefully if @antv/layout is not available.
 */
export async function organizeLayout(graph) {
    try {
        const { DagreLayout } = await import('@antv/layout');

        const nodes = [];
        const edges = [];

        graph.getNodes().forEach(node => {
            const pos = node.getPosition();
            const size = node.getSize();
            nodes.push({
                id: node.id,
                x: pos.x,
                y: pos.y,
                width: size.width,
                height: size.height,
            });
        });

        graph.getEdges().forEach(edge => {
            const source = edge.getSourceCellId();
            const target = edge.getTargetCellId();
            if (source && target) {
                edges.push({ source, target });
            }
        });

        if (nodes.length === 0) return;

        const layout = new DagreLayout({
            type: 'dagre',
            rankdir: 'TB',
            nodesep: 60,
            ranksep: 80,
        });

        const model = layout.layout({ nodes, edges });

        if (model.nodes) {
            model.nodes.forEach(n => {
                const cell = graph.getCellById(n.id);
                if (cell && cell.isNode()) {
                    cell.position(n.x, n.y, { transition: { duration: 300 } });
                }
            });
        }
    } catch (e) {
        // @antv/layout not installed, use simple vertical layout
        console.warn('Auto-layout unavailable, using simple layout:', e.message);
        simpleVerticalLayout(graph);
    }
}

/**
 * Simple vertical layout fallback when dagre is not available.
 */
function simpleVerticalLayout(graph) {
    const nodes = graph.getNodes();
    if (nodes.length === 0) return;

    const startX = 300;
    let currentY = 60;
    const spacing = 60;

    nodes.forEach(node => {
        node.position(startX, currentY, { transition: { duration: 300 } });
        currentY += node.getSize().height + spacing;
    });
}
