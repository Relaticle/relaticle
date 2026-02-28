import { Shape } from '@antv/x6';

export function registerActionNode() {
    Shape.HTML.register({
        shape: 'workflow-action',
        width: 200,
        height: 60,
        html(cell) {
            const data = cell.getData() || {};
            const label = data.config?.action_type || data.actionType || 'Do something...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.className = 'workflow-node action-node';
            div.innerHTML = `
                <div class="node-header action-header">
                    <span class="node-icon">&#9654;</span>
                    <span class="node-title">Action</span>
                </div>
                <div class="node-body">
                    <span class="node-summary">${label}</span>
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
                            stroke: '#3b82f6',
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
                            stroke: '#3b82f6',
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
