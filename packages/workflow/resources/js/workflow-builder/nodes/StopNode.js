import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_STOP } from '../icons.js';

export function registerStopNode() {
    Shape.HTML.register({
        shape: 'workflow-stop',
        width: 260,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const reason = data.config?.reason;
            const summary = reason || 'End workflow';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#ef4444',
                icon: ICON_STOP,
                label: 'Stop',
                category: 'Flow',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOnly,
    });
}
