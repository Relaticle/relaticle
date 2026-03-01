import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';

const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';

export function registerActionNode() {
    Shape.HTML.register({
        shape: 'workflow-action',
        width: 260,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const actionType = data.config?.action_type || data.actionType;
            const summary = actionType ? actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : 'Do something...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#3b82f6',
                icon: ICON,
                label: 'Action',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
