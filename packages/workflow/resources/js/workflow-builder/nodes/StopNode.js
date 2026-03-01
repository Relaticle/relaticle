import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';

const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>';

export function registerStopNode() {
    Shape.HTML.register({
        shape: 'workflow-stop',
        width: 240,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const reason = data.config?.reason;
            const summary = reason || 'End workflow';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#ef4444',
                icon: ICON,
                label: 'Stop',
                summary,
            });
            return div;
        },
        ports: portConfigs.inputOnly,
    });
}
