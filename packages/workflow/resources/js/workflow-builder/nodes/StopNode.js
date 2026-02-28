import { Shape } from '@antv/x6';

export function registerStopNode() {
    Shape.HTML.register({
        shape: 'workflow-stop',
        width: 200,
        height: 60,
        html(cell) {
            const data = cell.getData() || {};
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.className = 'workflow-node stop-node';
            div.innerHTML = `
                <div class="node-header stop-header">
                    <span class="node-icon">&#9209;</span>
                    <span class="node-title">Stop</span>
                </div>
                <div class="node-body">
                    <span class="node-summary">${data.label || 'End workflow'}</span>
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
                            stroke: '#ef4444',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                },
            },
            items: [{ id: 'in', group: 'in' }],
        },
    });
}
