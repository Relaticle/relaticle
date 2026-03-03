/**
 * Validates all nodes in the graph have required configuration.
 * Returns { errors, warnings } where errors block operations and warnings are informational.
 */
export function validateAllNodes(graph, applyVisuals = false) {
    const cells = graph.getCells();
    const errors = [];
    const warnings = [];

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
    const edges = graph.getEdges();
    const hasTrigger = nodes.some(n => n.getData()?.type === 'trigger');
    if (!hasTrigger && nodes.length > 0) {
        errors.push({ nodeId: null, message: 'Workflow is missing a Trigger block' });
    }

    // Warning: disconnected nodes (no incoming edges, not trigger)
    nodes.forEach(node => {
        const data = node.getData() || {};
        if (data.type === 'trigger') return;

        const hasIncoming = edges.some(e => {
            const target = e.getTargetCell();
            return target && target.id === node.id;
        });
        if (!hasIncoming) {
            warnings.push({
                nodeId: node.id,
                message: `"${data.label || data.actionType || node.id}" has no incoming connection`,
            });
            if (applyVisuals) {
                node.setData({ ...node.getData(), _warning: 'disconnected' }, { silent: true });
            }
        }
    });

    // Warning: dead-end nodes (no outgoing edges, not stop)
    nodes.forEach(node => {
        const data = node.getData() || {};
        if (data.type === 'stop') return;

        const hasOutgoing = edges.some(e => {
            const source = e.getSourceCell();
            return source && source.id === node.id;
        });
        if (!hasOutgoing) {
            warnings.push({
                nodeId: node.id,
                message: `"${data.label || data.actionType || node.id}" has no next step`,
            });
            if (applyVisuals) {
                node.setData({ ...node.getData(), _warning: 'dead-end' }, { silent: true });
            }
        }
    });

    // Warning: condition nodes with only one branch
    nodes.forEach(node => {
        const data = node.getData() || {};
        if (data.type !== 'condition') return;

        const outgoing = edges.filter(e => {
            const source = e.getSourceCell();
            return source && source.id === node.id;
        });
        if (outgoing.length === 1) {
            warnings.push({
                nodeId: node.id,
                message: `Condition "${data.label || node.id}" only has one branch connected`,
            });
            if (applyVisuals) {
                node.setData({ ...node.getData(), _warning: 'single-branch' }, { silent: true });
            }
        }
    });

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

    return { errors, warnings };
}

/**
 * Clear all validation visual indicators (errors and warnings) from the graph.
 */
export function clearValidationErrors(graph) {
    graph.getNodes().forEach(node => {
        const data = node.getData() || {};
        if (data._validationError || data._warning) {
            const { _validationError, _warning, ...rest } = data;
            node.setData(rest, { overwrite: true });
        }
    });
}
