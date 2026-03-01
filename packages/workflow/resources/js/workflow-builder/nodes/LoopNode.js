import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';

const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';

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
                icon: ICON,
                label: 'Loop',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
