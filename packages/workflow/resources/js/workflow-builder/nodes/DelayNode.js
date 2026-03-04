import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_DELAY } from '../icons.js';

export function registerDelayNode() {
    Shape.HTML.register({
        shape: 'workflow-delay',
        width: 280,
        height: 82,
        html(cell) {
            const data = cell.getData() || {};
            const duration = data.config?.duration;
            const unit = data.config?.unit || 'minutes';
            const summary = duration ? `Wait ${duration} ${unit}` : 'Wait...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#6b7280',
                icon: ICON_DELAY,
                label: 'Delay',
                category: 'Timing',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
