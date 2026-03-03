import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_TRIGGER } from '../icons.js';

export function registerTriggerNode() {
    Shape.HTML.register({
        shape: 'workflow-trigger',
        width: 260,
        height: 72,
        html(cell) {
            const data = cell.getData() || {};
            const event = data.config?.event;
            const entity = data.config?.entity_type;
            let summary = data.label || 'When...';
            if (event) {
                const eventLabels = { record_created: 'Record created', record_updated: 'Record updated', record_deleted: 'Record deleted', manual: 'Manual run', webhook: 'Webhook received', scheduled: 'Recurring schedule' };
                summary = eventLabels[event] || event;
                if (entity && ['record_created', 'record_updated', 'record_deleted'].includes(event)) {
                    summary += ` (${entity})`;
                }
            }
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#22c55e',
                icon: ICON_TRIGGER,
                label: 'Trigger',
                category: 'Trigger',
                summary,
                description: data.config?.description || '',
            });
            return div;
        },
        ports: portConfigs.outputOnly,
    });
}
