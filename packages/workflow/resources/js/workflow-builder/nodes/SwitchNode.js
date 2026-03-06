import { Shape } from '@antv/x6';
import { createNodeHTML } from './BaseNode.js';
import { ICON_GIT_BRANCH } from '../icons.js';

export function registerSwitchNode() {
    Shape.HTML.register({
        shape: 'workflow-switch',
        width: 280,
        height: 96,
        html(cell) {
            const data = cell.getData() || {};
            const cases = data.config?.cases || [];
            const field = data.config?.field;
            let summary = 'Set up branches...';
            if (field) {
                const fieldName = field.split('.').pop();
                const caseCount = cases.length + (data.config?.hasDefault !== false ? 1 : 0);
                summary = `Switch on ${fieldName} (${caseCount} branches)`;
            }
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#8b5cf6',
                icon: ICON_GIT_BRANCH,
                label: 'Switch',
                category: 'Decisions',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: {
            groups: {
                in: {
                    position: 'top',
                    attrs: {
                        circle: { r: 4, magnet: true, stroke: '#cbd5e1', strokeWidth: 1, fill: '#fff' },
                    },
                },
                out: {
                    position: 'bottom',
                    attrs: {
                        circle: { r: 4, magnet: true, stroke: '#8b5cf6', strokeWidth: 1.5, fill: '#fff' },
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
