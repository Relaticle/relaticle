import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';

const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';

export function registerDelayNode() {
    Shape.HTML.register({
        shape: 'workflow-delay',
        width: 240,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const duration = data.config?.duration;
            const unit = data.config?.unit || 'minutes';
            const summary = duration ? `Wait ${duration} ${unit}` : 'Wait...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#6b7280',
                icon: ICON,
                label: 'Delay',
                summary,
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
