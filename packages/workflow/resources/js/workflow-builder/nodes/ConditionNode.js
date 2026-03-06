import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_CONDITION } from '../icons.js';

export function registerConditionNode() {
    Shape.HTML.register({
        shape: 'workflow-condition',
        width: 280,
        height: 96,
        html(cell) {
            const data = cell.getData() || {};
            const conditions = data.config?.conditions;
            const OP_LABELS = {
                equals: 'is', not_equals: 'is not', contains: 'includes',
                greater_than: 'is more than', less_than: 'is less than',
                is_empty: 'is blank', is_not_empty: 'has a value', in: 'is one of',
            };
            let summary = 'Set up conditions...';
            if (Array.isArray(conditions) && conditions.length > 0) {
                const first = conditions[0];
                const op = OP_LABELS[first.operator] || (first.operator || '').replace(/_/g, ' ');
                const fieldName = (first.field || '').split('.').pop();
                if (fieldName && first.value) {
                    summary = `If ${fieldName} ${op} "${first.value}"`;
                } else if (fieldName && (first.operator === 'is_empty' || first.operator === 'is_not_empty')) {
                    summary = `If ${fieldName} ${op}`;
                } else if (fieldName) {
                    summary = `If ${fieldName} ${op}`;
                }
                if (conditions.length > 1) summary += ` (+${conditions.length - 1} more)`;
            } else if (data.config?.field) {
                const op = OP_LABELS[data.config.operator] || (data.config.operator || '').replace(/_/g, ' ');
                summary = `If ${data.config.field} ${op}`;
            }
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#f59e0b',
                icon: ICON_CONDITION,
                label: 'If / Else',
                category: 'Conditions',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.conditionPorts,
    });
}
