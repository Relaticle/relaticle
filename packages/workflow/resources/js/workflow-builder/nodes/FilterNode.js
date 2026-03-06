import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_FILTER } from '../icons.js';

const OP_LABELS = {
    equals: 'is', not_equals: 'is not', contains: 'includes',
    greater_than: 'is more than', less_than: 'is less than',
    is_empty: 'is blank', is_not_empty: 'has a value', in: 'is one of',
};

export function registerFilterNode() {
    Shape.HTML.register({
        shape: 'workflow-filter',
        width: 280,
        height: 90,
        html(cell) {
            const data = cell.getData() || {};
            const conditions = data.config?.conditions;
            let summary = 'Set up filter...';
            if (Array.isArray(conditions) && conditions.length > 0) {
                const first = conditions[0];
                const op = OP_LABELS[first.operator] || (first.operator || '').replace(/_/g, ' ');
                const fieldName = (first.field || '').split('.').pop();
                if (fieldName && first.value) {
                    summary = `Continue if ${fieldName} ${op} "${first.value}"`;
                } else if (fieldName && (first.operator === 'is_empty' || first.operator === 'is_not_empty')) {
                    summary = `Continue if ${fieldName} ${op}`;
                } else if (fieldName) {
                    summary = `Continue if ${fieldName} ${op}`;
                }
                if (conditions.length > 1) summary += ` (+${conditions.length - 1} more)`;
            }
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#f59e0b',
                icon: ICON_FILTER,
                label: 'Filter',
                category: 'Decisions',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.inputOutput,
    });
}
