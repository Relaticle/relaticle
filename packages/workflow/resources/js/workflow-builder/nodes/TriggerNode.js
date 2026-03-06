import { Shape } from '@antv/x6';
import { createNodeHTML, portConfigs } from './BaseNode.js';
import { ICON_TRIGGER } from '../icons.js';

export function registerTriggerNode() {
    Shape.HTML.register({
        shape: 'workflow-trigger',
        width: 280,
        height: 82,
        html(cell) {
            const data = cell.getData() || {};
            const event = data.config?.event;
            const entity = data.config?.entity_type;
            const singularMap = { people: 'person', companies: 'company', opportunities: 'opportunity', tasks: 'task', notes: 'note' };
            const entitySingular = entity ? (singularMap[entity] || entity) : 'record';
            let summary = 'Configure trigger...';
            if (event) {
                summary = ({
                    record_created: `When a ${entitySingular} is created`,
                    record_updated: `When a ${entitySingular} is updated`,
                    record_deleted: `When a ${entitySingular} is deleted`,
                    manual: 'Manual trigger',
                    webhook: 'When webhook is received',
                    scheduled: 'On a recurring schedule',
                })[event] || event;
            }
            const div = document.createElement('div');
            div.setAttribute('data-test', 'workflow-node');
            div.innerHTML = createNodeHTML(data, {
                color: '#8b5cf6',
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
