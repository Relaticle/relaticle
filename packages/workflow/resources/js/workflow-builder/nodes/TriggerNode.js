import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';

const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>';

export function registerTriggerNode() {
    Shape.HTML.register({
        shape: 'workflow-trigger',
        width: 260,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const summary = data.label || data.config?.event || 'When...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#22c55e',
                icon: ICON,
                label: 'Trigger',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.outputOnly,
    });
}
