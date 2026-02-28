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
                    <span class="node-icon"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg></span>
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
