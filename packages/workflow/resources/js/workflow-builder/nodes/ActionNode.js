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
                    <span class="node-icon"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></span>
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
