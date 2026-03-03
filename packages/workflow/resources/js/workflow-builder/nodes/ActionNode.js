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

export function registerActionNode() {
    Shape.HTML.register({
        shape: 'workflow-action',
        width: 260,
        height: 82,
        html(cell) {
            const data = cell.getData() || {};
            const actionType = data.config?.action_type || data.actionType;
            const meta = ACTION_META[actionType] || {};
            const summary = meta.label || (actionType ? actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : 'Do something...');
            const color = meta.color || '#3b82f6';
            const icon = meta.icon || ICON_RECORD;
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color,
                icon,
                label: summary,
                category: meta.category || 'Action',
                summary: data.config?.description || summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
