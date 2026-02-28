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
                    <span class="node-icon"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg></span>
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
