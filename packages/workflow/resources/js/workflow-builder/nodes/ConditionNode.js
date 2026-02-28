import { Shape } from '@antv/x6';

export function registerConditionNode() {
    Shape.HTML.register({
        shape: 'workflow-condition',
        width: 200,
        height: 70,
        html(cell) {
            const data = cell.getData() || {};
            const field = data.config?.field || '';
            const operator = data.config?.operator || '';
            const summary = field ? `${field} ${operator}` : 'If...';
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.className = 'workflow-node condition-node';
            div.innerHTML = `
                <div class="node-header condition-header">
                    <span class="node-icon"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg></span>
                    <span class="node-title">Condition</span>
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
                            stroke: '#eab308',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                },
                yes: {
                    position: { name: 'absolute', args: { x: '25%', y: '100%' } },
                    attrs: {
                        circle: {
                            r: 6,
                            magnet: true,
                            stroke: '#22c55e',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                    label: {
                        position: 'bottom',
                    },
                },
                no: {
                    position: { name: 'absolute', args: { x: '75%', y: '100%' } },
                    attrs: {
                        circle: {
                            r: 6,
                            magnet: true,
                            stroke: '#ef4444',
                            strokeWidth: 2,
                            fill: '#fff',
                        },
                    },
                    label: {
                        position: 'bottom',
                    },
                },
            },
            items: [
                { id: 'in', group: 'in' },
                { id: 'yes', group: 'yes', attrs: { text: { text: 'Yes' } } },
                { id: 'no', group: 'no', attrs: { text: { text: 'No' } } },
            ],
        },
    });
}
