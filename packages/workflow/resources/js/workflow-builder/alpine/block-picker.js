/**
 * Alpine.js Block Picker Component
 *
 * Provides the categorized block picker popover that appears when the user
 * clicks a + button on the canvas. Replaces the old sidebar drag-and-drop.
 */
import {
    ICON_TRIGGER,
    ICON_CONDITION,
    ICON_DELAY,
    ICON_LOOP,
    ICON_STOP,
    ICON_FILE_PLUS,
    ICON_FILE_PEN,
    ICON_FILE_SEARCH,
    ICON_FILE_X,
    ICON_MAIL,
    ICON_WEBHOOK,
    ICON_GLOBE,
    ICON_MESSAGE,
    ICON_FILE_TEXT,
    ICON_TAG,
    ICON_SPARKLES,
} from '../icons.js';

const COLORS = {
    trigger: '#22c55e',
    action: '#3b82f6',
    condition: '#f59e0b',
    delay: '#6b7280',
    loop: '#8b5cf6',
    stop: '#ef4444',
    record: '#0ea5e9',
    communication: '#10b981',
    integration: '#8b5cf6',
    ai: '#a855f7',
};

export function blockPickerData() {
    return {
        categories: [
            {
                name: 'Triggers',
                blocks: [
                    { type: 'trigger', label: 'Trigger', description: 'Start your workflow', icon: ICON_TRIGGER, color: COLORS.trigger },
                ],
            },
            {
                name: 'Records',
                blocks: [
                    { type: 'action', label: 'Create Record', description: 'Create a new record', actionType: 'create_record', icon: ICON_FILE_PLUS, color: COLORS.record },
                    { type: 'action', label: 'Update Record', description: 'Update an existing record', actionType: 'update_record', icon: ICON_FILE_PEN, color: COLORS.record },
                    { type: 'action', label: 'Find Records', description: 'Search for matching records', actionType: 'find_record', icon: ICON_FILE_SEARCH, color: COLORS.record },
                    { type: 'action', label: 'Delete Record', description: 'Delete a record', actionType: 'delete_record', icon: ICON_FILE_X, color: COLORS.record },
                ],
            },
            {
                name: 'AI',
                blocks: [
                    { type: 'action', label: 'Prompt Completion', description: 'Generate AI text from a prompt', actionType: 'prompt_completion', icon: ICON_MESSAGE, color: COLORS.ai },
                    { type: 'action', label: 'Summarize Record', description: 'Summarize record data with AI', actionType: 'summarize', icon: ICON_FILE_TEXT, color: COLORS.ai },
                    { type: 'action', label: 'Classify Record', description: 'Classify text into categories', actionType: 'classify', icon: ICON_TAG, color: COLORS.ai },
                ],
            },
            {
                name: 'Communication',
                blocks: [
                    { type: 'action', label: 'Send Email', description: 'Send an email notification', actionType: 'send_email', icon: ICON_MAIL, color: COLORS.communication },
                ],
            },
            {
                name: 'Integration',
                blocks: [
                    { type: 'action', label: 'Send Webhook', description: 'Send data to an external URL', actionType: 'send_webhook', icon: ICON_WEBHOOK, color: COLORS.integration },
                    { type: 'action', label: 'HTTP Request', description: 'Make an HTTP API call', actionType: 'http_request', icon: ICON_GLOBE, color: COLORS.integration },
                ],
            },
            {
                name: 'Conditions',
                blocks: [
                    { type: 'condition', label: 'If / Else', description: 'Branch based on a condition', icon: ICON_CONDITION, color: COLORS.condition },
                ],
            },
            {
                name: 'Timing',
                blocks: [
                    { type: 'delay', label: 'Delay', description: 'Wait before continuing', icon: ICON_DELAY, color: COLORS.delay },
                ],
            },
            {
                name: 'Flow',
                blocks: [
                    { type: 'loop', label: 'Loop', description: 'Iterate over a collection', icon: ICON_LOOP, color: COLORS.loop },
                    { type: 'stop', label: 'Stop', description: 'End the workflow', icon: ICON_STOP, color: COLORS.stop },
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
