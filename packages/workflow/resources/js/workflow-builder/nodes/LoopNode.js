import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_LOOP } from '../icons.js';

export function registerLoopNode() {
    Shape.HTML.register({
        shape: 'workflow-loop',
        width: 260,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const collection = data.config?.collection;
            const summary = collection ? `For each in ${collection}` : 'Loop...';
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
