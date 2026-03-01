/**
 * Alpine.js Variable Picker Component
 *
 * Provides {x} variable insertion for config panel input fields.
 * Walks the graph backward from the current node to find upstream
 * blocks and their output schemas.
 */

/**
 * Get upstream variables available for a given node.
 *
 * @param {object} graph - The X6 graph instance
 * @param {string} nodeId - The current node ID
 * @param {object} meta - Canvas API metadata (registered_actions, trigger_outputs)
 * @returns {Array<{source: string, outputs: Array}>}
 */
export function getUpstreamVariables(graph, nodeId, meta) {
    const variables = [];
    const visited = new Set();
    const queue = [nodeId];

    while (queue.length > 0) {
        const currentId = queue.shift();
        if (visited.has(currentId)) continue;
        visited.add(currentId);

        const cell = graph.getCellById(currentId);
        if (!cell || !cell.isNode()) continue;

        // Skip the current node itself (only look at upstream)
        if (currentId !== nodeId) {
            const data = cell.getData() || {};
            const outputs = getOutputsForNode(data, meta);
            if (outputs.length > 0) {
                variables.push({
                    source: data.label || data.actionType || data.type || 'Unknown',
                    nodeId: currentId,
                    outputs,
                });
            }
        }

        // Walk backward through incoming edges
        const incomingEdges = graph.getIncomingEdges(cell) || [];
        for (const edge of incomingEdges) {
            const sourceNode = edge.getSourceNode();
            if (sourceNode) {
                queue.push(sourceNode.id);
            }
        }
    }

    // Add built-in variables
    variables.push({
        source: 'Built-in',
        nodeId: '__builtin',
        outputs: [
            { key: 'now', type: 'string', label: 'Current Timestamp' },
            { key: 'today', type: 'string', label: "Today's Date" },
        ],
    });

    return variables;
}

/**
 * Get the output schema for a given node based on its type and config.
 */
function getOutputsForNode(data, meta) {
    const type = data.type;

    if (type === 'trigger') {
        const event = data.config?.event || 'manual';
        const triggerOutputs = meta?.trigger_outputs?.[event] || {};
        return Object.entries(triggerOutputs).map(([key, def]) => ({
            key,
            type: def.type,
            label: def.label,
        }));
    }

    if (type === 'action') {
        const actionType = data.actionType;
        if (actionType && meta?.registered_actions?.[actionType]?.outputSchema) {
            const schema = meta.registered_actions[actionType].outputSchema;
            return Object.entries(schema).map(([key, def]) => ({
                key,
                type: def.type,
                label: def.label,
            }));
        }
    }

    if (type === 'condition') {
        return [
            { key: 'result', type: 'boolean', label: 'Condition Result' },
        ];
    }

    if (type === 'loop') {
        return [
            { key: 'item', type: 'object', label: 'Current Item' },
            { key: 'index', type: 'number', label: 'Current Index' },
            { key: 'item_count', type: 'number', label: 'Item Count' },
        ];
    }

    return [];
}

/**
 * Alpine component for the variable picker popover.
 */
export function variablePickerComponent() {
    return {
        varPickerOpen: false,
        varPickerTarget: null,
        varPickerPos: { x: 0, y: 0 },
        variables: [],

        openVariablePicker(inputEl, nodeId) {
            const graph = window.__wfGraph;
            const meta = window.__wfMeta;
            if (!graph || !nodeId) return;

            this.variables = getUpstreamVariables(graph, nodeId, meta);
            this.varPickerTarget = inputEl;

            // Position near the input
            const rect = inputEl.getBoundingClientRect();
            this.varPickerPos = {
                x: rect.right + 4,
                y: rect.top,
            };

            this.varPickerOpen = true;
        },

        insertVariable(source, key) {
            if (!this.varPickerTarget) return;
            const variable = `{{${source}.${key}}}`;
            const input = this.varPickerTarget;
            const start = input.selectionStart || input.value.length;
            const end = input.selectionEnd || input.value.length;
            input.value = input.value.substring(0, start) + variable + input.value.substring(end);
            input.dispatchEvent(new Event('change', { bubbles: true }));
            this.varPickerOpen = false;
        },

        closeVariablePicker() {
            this.varPickerOpen = false;
            this.varPickerTarget = null;
        },
    };
}
