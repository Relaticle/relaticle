import { Shape } from '@antv/x6';

export function registerDelayNode() {
    Shape.HTML.register({
        shape: 'workflow-delay',
        width: 200,
        height: 60,
        html(cell) {
            const data = cell.getData() || {};
            const duration = data.config?.duration || '';
            const unit = data.config?.unit || 'minutes';
            const summary = duration ? `Wait ${duration} ${unit}` : 'Wait...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.className = 'workflow-node delay-node';
            div.innerHTML = `
                <div class="node-header delay-header">
                    <span class="node-icon">&#9201;</span>
                    <span class="node-title">Delay</span>
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
                            stroke: '#6b7280',
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
                            stroke: '#6b7280',
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
