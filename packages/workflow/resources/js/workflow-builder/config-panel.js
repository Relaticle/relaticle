/**
 * Validates all nodes in the graph have required configuration.
 * Returns an array of errors and optionally applies visual indicators.
 */
export function validateAllNodes(graph, applyVisuals = false) {
    const cells = graph.getCells();
    const errors = [];

    cells.forEach(cell => {
        if (!cell.isNode()) return;
        const data = cell.getData() || {};
        const type = data.type;

        if (type === 'action' && !data.actionType) {
            errors.push({ nodeId: cell.id, message: 'Action type not configured' });
        }

        if (type === 'trigger' && !data.config?.event) {
            errors.push({ nodeId: cell.id, message: 'Trigger event not configured' });
        }

        if (type === 'condition') {
            const conditions = data.config?.conditions;
            const hasLegacyField = data.config?.field;
            if (!hasLegacyField && (!Array.isArray(conditions) || conditions.length === 0)) {
                errors.push({ nodeId: cell.id, message: 'Condition has no rules configured' });
            }
        }
    });

    // Check graph connectivity — every node should have at least one edge
    const nodes = graph.getNodes();
    const hasTrigger = nodes.some(n => n.getData()?.type === 'trigger');
    if (!hasTrigger && nodes.length > 0) {
        errors.push({ nodeId: null, message: 'Workflow is missing a Trigger block' });
    }

    // Apply visual indicators on nodes with errors
    if (applyVisuals) {
        const errorNodeIds = new Set(errors.map(e => e.nodeId).filter(Boolean));
        nodes.forEach(node => {
            const data = node.getData() || {};
            if (errorNodeIds.has(node.id)) {
                node.setData({ ...data, _validationError: true }, { overwrite: true });
            } else {
                const { _validationError, ...rest } = data;
                if (_validationError) {
                    node.setData(rest, { overwrite: true });
                }
            }
        });
    }

    return errors;
}

/**
 * Clear all validation visual indicators from the graph.
 */
export function clearValidationErrors(graph) {
    graph.getNodes().forEach(node => {
        const data = node.getData() || {};
        if (data._validationError) {
            const { _validationError, ...rest } = data;
            node.setData(rest, { overwrite: true });
        }
    });
}
