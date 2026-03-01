/**
 * Alpine.js Block Picker Component
 *
 * Provides the categorized block picker popover that appears when the user
 * clicks a + button on the canvas. Replaces the old sidebar drag-and-drop.
 */

const ICONS = {
    trigger: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
    action: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>',
    condition: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>',
    delay: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    loop: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
    stop: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>',
};

const COLORS = {
    trigger: '#22c55e',
    action: '#3b82f6',
    condition: '#f59e0b',
    delay: '#6b7280',
    loop: '#8b5cf6',
    stop: '#ef4444',
};

export function blockPickerData() {
    return {
        categories: [
            {
                name: 'Triggers',
                blocks: [
                    { type: 'trigger', label: 'Trigger', icon: ICONS.trigger, color: COLORS.trigger },
                ],
            },
            {
                name: 'Actions',
                blocks: [
                    { type: 'action', label: 'Send Email', actionType: 'send_email', icon: ICONS.action, color: COLORS.action },
                    { type: 'action', label: 'Send Webhook', actionType: 'send_webhook', icon: ICONS.action, color: COLORS.action },
                    { type: 'action', label: 'HTTP Request', actionType: 'http_request', icon: ICONS.action, color: COLORS.action },
                ],
            },
            {
                name: 'Logic',
                blocks: [
                    { type: 'condition', label: 'If / Else', icon: ICONS.condition, color: COLORS.condition },
                ],
            },
            {
                name: 'Timing',
                blocks: [
                    { type: 'delay', label: 'Delay', icon: ICONS.delay, color: COLORS.delay },
                ],
            },
            {
                name: 'Flow',
                blocks: [
                    { type: 'loop', label: 'Loop', icon: ICONS.loop, color: COLORS.loop },
                    { type: 'stop', label: 'Stop', icon: ICONS.stop, color: COLORS.stop },
                ],
            },
        ],

        get filteredCategories() {
            const search = this.blockPickerSearch;
            if (!search) return this.categories;
            const q = search.toLowerCase();
            return this.categories
                .map(cat => ({
                    ...cat,
                    blocks: cat.blocks.filter(b => b.label.toLowerCase().includes(q)),
                }))
                .filter(cat => cat.blocks.length > 0);
        },
    };
}

const SHAPE_MAP = {
    trigger: 'workflow-trigger',
    action: 'workflow-action',
    condition: 'workflow-condition',
    delay: 'workflow-delay',
    loop: 'workflow-loop',
    stop: 'workflow-stop',
};

/**
 * Add a block to the graph and connect it to the source node.
 *
 * @param {object} graph - The X6 graph instance
 * @param {object} block - The block definition from the picker
 * @param {string|null} sourceNodeId - The node to connect from (if any)
 * @param {string|null} sourcePortId - The port to connect from
 * @param {{x: number, y: number}|null} position - Override position in graph coords
 */
export function addBlockToGraph(graph, block, sourceNodeId, sourcePortId, position) {
    const shape = SHAPE_MAP[block.type];
    if (!shape) return null;

    const nodeId = `${block.type}-${Date.now()}`;
    let x = position?.x ?? 300;
    let y = position?.y ?? 200;

    // Position below the source node if one exists
    if (sourceNodeId) {
        const sourceNode = graph.getCellById(sourceNodeId);
        if (sourceNode) {
            const pos = sourceNode.getPosition();
            const size = sourceNode.getSize();
            x = pos.x;
            y = pos.y + size.height + 80;
        }
    }

    const data = {
        type: block.type,
        nodeId,
        config: {},
    };

    if (block.actionType) {
        data.actionType = block.actionType;
    }

    const node = graph.addNode({
        id: nodeId,
        shape,
        x,
        y,
        data,
    });

    // Auto-connect edge from source to new node
    if (sourceNodeId) {
        const edgeId = `edge-${Date.now()}`;
        graph.addEdge({
            id: edgeId,
            source: { cell: sourceNodeId, port: sourcePortId || 'out' },
            target: { cell: nodeId, port: 'in' },
        });
    }

    return node;
}
