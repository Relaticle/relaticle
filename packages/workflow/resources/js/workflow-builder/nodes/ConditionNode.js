import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_CONDITION } from '../icons.js';

export function registerConditionNode() {
    Shape.HTML.register({
        shape: 'workflow-condition',
        width: 260,
        height: 88,
        html(cell) {
            const data = cell.getData() || {};
            const conditions = data.config?.conditions;
            let summary = 'If / Else';
            if (Array.isArray(conditions) && conditions.length > 0) {
                const first = conditions[0];
                summary = first.field ? `${first.field} ${(first.operator || '').replace(/_/g, ' ')}` : 'If / Else';
                if (conditions.length > 1) summary += ` (+${conditions.length - 1} more)`;
            } else if (data.config?.field) {
                summary = `${data.config.field} ${(data.config.operator || '').replace(/_/g, ' ')}`;
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
