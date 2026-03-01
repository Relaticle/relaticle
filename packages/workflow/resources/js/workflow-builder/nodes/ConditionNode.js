import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';

const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>';

export function registerConditionNode() {
    Shape.HTML.register({
        shape: 'workflow-condition',
        width: 240,
        height: 80,
        html(cell) {
            const data = cell.getData() || {};
            const field = data.config?.field || '';
            const operator = data.config?.operator || '';
            const summary = field ? `${field} ${operator.replace(/_/g, ' ')}` : 'If / Else';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#f59e0b',
                icon: ICON,
                label: 'Condition',
                summary,
            });
            return div;
        },
        ports: portConfigs.conditionPorts,
    });
}
