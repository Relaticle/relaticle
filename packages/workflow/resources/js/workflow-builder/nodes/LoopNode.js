import { Shape } from '@antv/x6';

export function registerLoopNode() {
    Shape.HTML.register({
        shape: 'workflow-loop',
        width: 200,
        height: 60,
        html(cell) {
            const data = cell.getData() || {};
            const collection = data.config?.collection || '';
            const summary = collection ? `Each in ${collection}` : 'For each...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.className = 'workflow-node loop-node';
            div.innerHTML = `
                <div class="node-header loop-header">
                    <span class="node-icon">&#128260;</span>
                    <span class="node-title">Loop</span>
                </div>
                <div class="node-body">
                    <span class="node-summary">${summary}</span>
                </div>
            `;
            return div;
        },
        ports: {
            groups: {
                in: {
                    position: 'top',
                    attrs: {
                        circle: {
                            r: 6,
                            magnet: true,
                            stroke: '#8b5cf6',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                },
                out: {
                    position: 'bottom',
                    attrs: {
                        circle: {
                            r: 6,
                            magnet: true,
                            stroke: '#8b5cf6',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                },
            },
            items: [
                { id: 'in', group: 'in' },
                { id: 'out', group: 'out' },
            ],
        },
    });
}
