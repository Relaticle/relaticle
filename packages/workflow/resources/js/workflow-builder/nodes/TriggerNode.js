import { Shape } from '@antv/x6';

export function registerTriggerNode() {
    Shape.HTML.register({
        shape: 'workflow-trigger',
        width: 200,
        height: 60,
        html(cell) {
            const data = cell.getData() || {};
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.className = 'workflow-node trigger-node';
            div.innerHTML = `
                <div class="node-header trigger-header">
                    <span class="node-icon">&#9889;</span>
                    <span class="node-title">Trigger</span>
                </div>
                <div class="node-body">
                    <span class="node-summary">${data.label || 'When...'}</span>
                </div>
            `;
            return div;
        },
        ports: {
            groups: {
                out: {
                    position: 'bottom',
                    attrs: {
                        circle: {
                            r: 6,
                            magnet: true,
                            stroke: '#22c55e',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                },
            },
            items: [{ id: 'out', group: 'out' }],
        },
    });
}
