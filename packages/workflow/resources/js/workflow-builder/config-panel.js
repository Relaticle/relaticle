/**
 * Validates all nodes in the graph have required configuration.
 * Used by the save logic before persisting canvas data.
 */
export function validateAllNodes(graph) {
    const cells = graph.getCells();
    const errors = [];

    cells.forEach(cell => {
        if (!cell.isNode()) return;
        const data = cell.getData() || {};
        const type = data.type;

        if (type === 'action' && !data.actionType) {
            errors.push({ nodeId: cell.id, message: 'Action type not configured' });
        }
    });

    return errors;
}
