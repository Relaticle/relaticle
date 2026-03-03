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
    ICON_CALCULATOR,
    ICON_BAR_CHART,
    ICON_CLOCK_UP,
    ICON_DICE,
    ICON_MEGAPHONE,
    ICON_PARTY,
    ICON_BRACES,
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
    utility: '#f97316',
};

const SINGULAR_MAP = { people: 'person', companies: 'company', opportunities: 'opportunity', tasks: 'task', notes: 'note' };

/**
 * Generate a contextual description for a block based on the source node's config.
 */
export function getContextualDescription(block, sourceNode) {
    if (!sourceNode) return block.description;

    const sourceData = sourceNode.getData() || {};
    const sourceConfig = sourceData.config || {};
    const entity = sourceConfig.entity_type;
    const entitySingular = entity ? (SINGULAR_MAP[entity] || entity) : null;

    if (!entitySingular) return block.description;

    const actionType = block.actionType;
    const contextMap = {
        create_record: `Create a new record when a ${entitySingular} triggers`,
        update_record: `Update the triggered ${entitySingular} record`,
        find_record: `Find records related to the ${entitySingular}`,
        delete_record: `Delete the triggered ${entitySingular} record`,
        send_email: `Send an email about the ${entitySingular}`,
        send_webhook: `Send ${entitySingular} data to an external URL`,
        http_request: `Make an API call with ${entitySingular} data`,
        prompt_completion: `Generate AI text about the ${entitySingular}`,
        summarize: `Summarize the ${entitySingular} with AI`,
        classify: `Classify the ${entitySingular} into categories`,
        formula: `Calculate a value from ${entitySingular} fields`,
        aggregate: `Aggregate values from ${entitySingular} records`,
        broadcast_message: `Broadcast a message about the ${entitySingular}`,
    };

    const typeContextMap = {
        condition: `Branch based on ${entitySingular} field values`,
        delay: `Wait before processing the ${entitySingular}`,
        loop: `Iterate over ${entitySingular}-related data`,
        stop: 'End the workflow',
    };

    if (actionType && contextMap[actionType]) return contextMap[actionType];
    if (block.type && typeContextMap[block.type]) return typeContextMap[block.type];
    return block.description;
}

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
                    { type: 'action', label: 'Broadcast Message', description: 'Send a message to multiple recipients', actionType: 'broadcast_message', icon: ICON_MEGAPHONE, color: COLORS.communication },
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
                name: 'Utilities',
                blocks: [
                    { type: 'action', label: 'Formula', description: 'Calculate a value using an expression', actionType: 'formula', icon: ICON_CALCULATOR, color: COLORS.utility },
                    { type: 'action', label: 'Aggregate', description: 'Aggregate values from records', actionType: 'aggregate', icon: ICON_BAR_CHART, color: COLORS.utility },
                    { type: 'action', label: 'Adjust Time', description: 'Add or subtract time from a date', actionType: 'adjust_time', icon: ICON_CLOCK_UP, color: COLORS.utility },
                    { type: 'action', label: 'Random Number', description: 'Generate a random number', actionType: 'random_number', icon: ICON_DICE, color: COLORS.utility },
                    { type: 'action', label: 'Parse JSON', description: 'Parse a JSON string into data', actionType: 'parse_json', icon: ICON_BRACES, color: COLORS.utility },
                    { type: 'action', label: 'Celebration', description: 'Mark a milestone achievement', actionType: 'celebration', icon: ICON_PARTY, color: COLORS.utility },
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

        // Note: filteredCategories getter is defined in the main component
        // (index.js workflowBuilderFactory) AFTER the spread, because getters
        // are lost when spreading objects via the ... operator.
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

    if (sourceNodeId) {
        // Connected placement: below the source node
        const sourceNode = graph.getCellById(sourceNodeId);
        if (sourceNode) {
            const pos = sourceNode.getPosition();
            const size = sourceNode.getSize();
            x = pos.x;
            y = pos.y + size.height + 80;
        }
    } else if (!position) {
        // Orphan placement: find the lowest existing node and place below it
        const nodes = graph.getNodes();
        if (nodes.length > 0) {
            let maxY = -Infinity;
            for (const existingNode of nodes) {
                const pos = existingNode.getPosition();
                const size = existingNode.getSize();
                const bottom = pos.y + size.height;
                if (bottom > maxY) {
                    maxY = bottom;
                    x = pos.x;
                }
            }
            y = maxY + 80;
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

    // Apply smart defaults based on block type
    const actionType = block.actionType || block.type;
    const smartDefaults = {
        delay: { duration: 5, unit: 'minutes' },
        stop: { reason: 'Workflow complete' },
        http_request: { method: 'POST', headers: '{"Content-Type": "application/json"}' },
        aggregate: { operation: 'sum' },
        adjust_time: { amount: 1, unit: 'days', direction: 'add' },
        random_number: { min: 1, max: 100 },
        send_email: { subject: '', body: '', to: '' },
        send_webhook: { method: 'POST' },
        prompt_completion: { provider: 'anthropic', model: 'claude-haiku-4-5-20251001', max_tokens: 500, temperature: 0.7 },
        summarize: { record_source: 'trigger', provider: 'anthropic', model: 'claude-haiku-4-5-20251001' },
        classify: { provider: 'anthropic', model: 'claude-haiku-4-5-20251001', categories: ['Positive', 'Negative', 'Neutral'] },
        broadcast_message: { channel: 'default' },
        celebration: { type: 'confetti' },
        update_record: { record_source: 'trigger' },
        delete_record: { record_source: 'trigger' },
        find_record: {},
        create_record: { field_mappings: [] },
        formula: {},
        parse_json: {},
        condition: { match: 'all', conditions: [] },
        loop: {},
    };

    if (smartDefaults[actionType]) {
        const currentData = node.getData() || {};
        node.setData({
            ...currentData,
            config: { ...(currentData.config || {}), ...smartDefaults[actionType] },
        }, { silent: true });
    }

    // Propagate entity_type from trigger to entity-aware nodes
    const triggerNode = graph.getNodes().find(n => (n.getData() || {}).type === 'trigger');
    const triggerConfig = triggerNode?.getData()?.config || {};
    const entityType = triggerConfig.entity_type;

    if (entityType) {
        // Record actions get entity_type directly
        if (['create_record', 'find_record', 'update_record', 'delete_record'].includes(actionType)) {
            const currentData = node.getData() || {};
            node.setData({
                ...currentData,
                config: { ...(currentData.config || {}), entity_type: entityType },
            }, { silent: true });
        }
        // Store trigger entity context on condition/loop for field resolver awareness
        if (['condition', 'loop'].includes(block.type)) {
            const currentData = node.getData() || {};
            node.setData({
                ...currentData,
                config: { ...(currentData.config || {}), _triggerEntity: entityType },
            }, { silent: true });
        }
    }

    // Auto-connect edge from source to new node
    if (sourceNodeId) {
        const sourceCell = graph.getCellById(sourceNodeId);
        const sourceData = sourceCell?.getData() || {};
        const edgeId = `edge-${Date.now()}`;
        const portId = sourcePortId || (sourceData.type === 'condition' ? 'out-yes' : 'out');

        // Build edge config with condition labels
        const edgeConfig = {
            id: edgeId,
            source: { cell: sourceNodeId, port: portId },
            target: { cell: nodeId, port: 'in' },
        };

        if (sourceData.type === 'condition') {
            const isYes = portId === 'out-yes';
            const labelText = isYes ? 'does match' : 'does not match';
            edgeConfig.labels = [{
                attrs: {
                    label: { text: labelText, fill: '#fff', fontSize: 11, fontWeight: 600 },
                    rect: { ref: 'label', fill: isYes ? '#22c55e' : '#ef4444', rx: 10, ry: 10, refWidth: '140%', refHeight: '140%', refX: '-20%', refY: '-20%' },
                },
            }];
            edgeConfig.attrs = {
                line: {
                    stroke: isYes ? '#22c55e' : '#ef4444',
                    strokeWidth: 1.5,
                    targetMarker: { name: 'block', width: 10, height: 6 },
                },
            };
        }

        graph.addEdge(edgeConfig);
    }

    return node;
}
