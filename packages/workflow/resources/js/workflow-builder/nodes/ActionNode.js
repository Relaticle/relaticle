import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import {
    ICON_RECORD,
    ICON_MAIL,
    ICON_WEBHOOK,
    ICON_GLOBE,
    ICON_FILE_PLUS,
    ICON_FILE_PEN,
    ICON_FILE_SEARCH,
    ICON_FILE_X,
    ICON_MESSAGE,
    ICON_FILE_TEXT,
    ICON_TAG,
    ICON_CALCULATOR,
    ICON_BAR_CHART,
    ICON_CLOCK_UP,
    ICON_DICE,
    ICON_MEGAPHONE,
    ICON_PARTY,
    ICON_BRACES,
    ICON_SPARKLES,
} from '../icons.js';

const ACTION_META = {
    send_email:         { label: 'Send Email',         category: 'Communication', color: '#10b981', icon: ICON_MAIL },
    send_webhook:       { label: 'Send Webhook',       category: 'Integration',   color: '#8b5cf6', icon: ICON_WEBHOOK },
    http_request:       { label: 'HTTP Request',       category: 'Integration',   color: '#8b5cf6', icon: ICON_GLOBE },
    create_record:      { label: 'Create Record',      category: 'Records',       color: '#0ea5e9', icon: ICON_FILE_PLUS },
    update_record:      { label: 'Update Record',      category: 'Records',       color: '#0ea5e9', icon: ICON_FILE_PEN },
    find_record:        { label: 'Find Records',       category: 'Records',       color: '#0ea5e9', icon: ICON_FILE_SEARCH },
    delete_record:      { label: 'Delete Record',      category: 'Records',       color: '#ef4444', icon: ICON_FILE_X },
    prompt_completion:  { label: 'Prompt Completion',   category: 'AI',            color: '#a855f7', icon: ICON_MESSAGE },
    summarize:          { label: 'Summarize Record',    category: 'AI',            color: '#a855f7', icon: ICON_FILE_TEXT },
    classify:           { label: 'Classify Record',     category: 'AI',            color: '#a855f7', icon: ICON_TAG },
    formula:            { label: 'Formula',             category: 'Logic',         color: '#f59e0b', icon: ICON_CALCULATOR },
    aggregate:          { label: 'Aggregate',           category: 'Logic',         color: '#f59e0b', icon: ICON_BAR_CHART },
    adjust_time:        { label: 'Adjust Time',         category: 'Logic',         color: '#f59e0b', icon: ICON_CLOCK_UP },
    random_number:      { label: 'Random Number',       category: 'Logic',         color: '#f59e0b', icon: ICON_DICE },
    broadcast_message:  { label: 'Broadcast Message',   category: 'Communication', color: '#10b981', icon: ICON_MEGAPHONE },
    celebration:        { label: 'Celebration',          category: 'Fun',           color: '#ec4899', icon: ICON_PARTY },
    parse_json:         { label: 'Parse JSON',           category: 'Logic',         color: '#f59e0b', icon: ICON_BRACES },
};

function truncate(str, max) {
    if (!str) return '';
    return str.length > max ? str.slice(0, max) + '...' : str;
}

function getActionSummary(actionType, config) {
    const singularMap = { people: 'person', companies: 'company', opportunities: 'opportunity', tasks: 'task', notes: 'note' };
    const entity = config.entity_type;
    const entityLabel = entity ? (singularMap[entity] || entity) : null;

    switch (actionType) {
        case 'create_record':
            return entityLabel ? `Create a new ${entityLabel}` : 'Create a new record';
        case 'update_record':
            return entityLabel ? `Update the ${entityLabel}` : 'Update a record';
        case 'find_record':
            return entityLabel ? `Find ${entity}` : 'Find records';
        case 'delete_record':
            return entityLabel ? `Delete the ${entityLabel}` : 'Delete a record';
        case 'send_email': {
            const to = config.to;
            if (to) return `Email to ${truncate(to, 30)}`;
            return 'Send an email';
        }
        case 'send_webhook':
        case 'http_request': {
            const url = config.url;
            if (url) return `${(config.method || 'POST')} ${truncate(url, 25)}`;
            return actionType === 'send_webhook' ? 'Send webhook' : 'HTTP request';
        }
        case 'prompt_completion': {
            const prompt = config.prompt;
            if (prompt) return `AI: ${truncate(prompt, 30)}`;
            return 'AI prompt';
        }
        case 'summarize':
            return entityLabel ? `Summarize ${entityLabel}` : 'Summarize record';
        case 'classify': {
            const cats = config.categories;
            if (Array.isArray(cats) && cats.length > 0) return `Classify into ${cats.length} categories`;
            return 'Classify text';
        }
        case 'formula': {
            const f = config.formula;
            if (f) return `= ${truncate(f, 30)}`;
            return 'Calculate formula';
        }
        case 'aggregate':
            return `${(config.operation || 'sum').toUpperCase()} of values`;
        case 'adjust_time':
            return config.amount ? `${config.direction || 'add'} ${config.amount} ${config.unit || 'days'}` : 'Adjust time';
        case 'random_number':
            return `Random ${config.min ?? 1}\u2013${config.max ?? 100}`;
        case 'broadcast_message':
            return config.message ? `Broadcast: ${truncate(config.message, 25)}` : 'Broadcast message';
        case 'celebration':
            return config.message ? truncate(config.message, 30) : 'Celebration';
        case 'parse_json':
            return config.json_path ? `Parse ${truncate(config.json_path, 25)}` : 'Parse JSON';
        default:
            return null;
    }
}

export function registerActionNode() {
    Shape.HTML.register({
        shape: 'workflow-action',
        width: 280,
        height: 90,
        html(cell) {
            const data = cell.getData() || {};
            const actionType = data.config?.action_type || data.actionType;
            const meta = ACTION_META[actionType] || {};
            const config = data.config || {};
            const color = meta.color || '#3b82f6';
            const icon = meta.icon || ICON_RECORD;
            const label = meta.label || (actionType ? actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : 'Action');

            // Generate config-aware summary
            const summary = config.description || getActionSummary(actionType, config) || label;

            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color,
                icon,
                label,
                category: meta.category || 'Action',
                summary,
                description: '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
