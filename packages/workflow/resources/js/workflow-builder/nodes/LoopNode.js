import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_LOOP } from '../icons.js';

export function registerLoopNode() {
    Shape.HTML.register({
        shape: 'workflow-loop',
        width: 280,
        height: 82,
        html(cell) {
            const data = cell.getData() || {};
            const collection = data.config?.collection;
            const summary = collection
                ? `For each in ${collection.split('.').pop()}`
                : 'Configure loop...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#8b5cf6',
                icon: ICON_LOOP,
                label: 'Loop',
                category: 'Flow',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
