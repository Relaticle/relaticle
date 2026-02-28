export function validateAllNodes(graph) {
    const cells = graph.getCells();
    const errors = [];

    cells.forEach(cell => {
        if (!cell.isNode()) return;
        const data = cell.getData() || {};
        const type = data.type;

        if (type === 'action' && !data.actionType) {
            errors.push({ nodeId: cell.id, message: 'Action type not configured' });
        }
    });

    return errors;
}

let _registeredActions = null;

async function fetchRegisteredActions(workflowId) {
    if (_registeredActions) return _registeredActions;
    try {
        const app = document.getElementById('workflow-builder-app');
        const wfId = workflowId || app?.dataset.workflowId;
        if (!wfId) return {};
        const resp = await fetch(`/workflow/api/workflows/${wfId}/canvas`);
        if (!resp.ok) return {};
        const data = await resp.json();
        _registeredActions = data.meta?.registered_actions || {};
        return _registeredActions;
    } catch {
        return {};
    }
}

export function initConfigPanel(graph) {
    const panel = document.getElementById('config-panel');
    const body = document.getElementById('config-panel-body');
    const closeBtn = document.getElementById('config-panel-close');

    // Pre-fetch registered actions
    fetchRegisteredActions();

    closeBtn?.addEventListener('click', () => {
        panel.style.display = 'none';
    });

    graph.on('node:click', async ({ node }) => {
        const data = node.getData() || {};
        const actions = await fetchRegisteredActions();
        body.innerHTML = renderConfig(data, actions);
        panel.style.display = 'block';

        // Bind change handlers to save config back to node
        const bindHandlers = () => {
            body.querySelectorAll('input, select, textarea').forEach((input) => {
                input.addEventListener('change', () => {
                    const currentData = node.getData() || {};
                    const updated = { ...currentData };
                    const config = { ...(currentData.config || {}) };

                    body.querySelectorAll('[data-config-key]').forEach((el) => {
                        config[el.dataset.configKey] = el.value;
                    });
                    updated.config = config;

                    // Handle action_type separately — it's a top-level node property
                    const actionTypeEl = body.querySelector('[data-node-key="actionType"]');
                    if (actionTypeEl) {
                        updated.actionType = actionTypeEl.value;
                    }

                    node.setData(updated, { overwrite: true });

                    // Re-render when action type changes to show its config fields
                    if (input.dataset.nodeKey === 'actionType') {
                        body.innerHTML = renderConfig(updated, actions);
                        bindHandlers();
                    }
                });
            });
        };
        bindHandlers();
    });

    graph.on('blank:click', () => {
        panel.style.display = 'none';
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function renderConfig(data, registeredActions) {
    const type = data.type || 'unknown';
    let html = `
        <div class="config-field">
            <label>Type</label>
            <input type="text" value="${escapeHtml(type)}" disabled>
        </div>
    `;

    if (type === 'trigger') {
        html += `
            <div class="config-field">
                <label>Trigger Event</label>
                <select data-config-key="event">
                    <option value="">Select event...</option>
                    <option value="record_created"${data.config?.event === 'record_created' ? ' selected' : ''}>Record Created</option>
                    <option value="record_updated"${data.config?.event === 'record_updated' ? ' selected' : ''}>Record Updated</option>
                    <option value="record_deleted"${data.config?.event === 'record_deleted' ? ' selected' : ''}>Record Deleted</option>
                    <option value="manual"${data.config?.event === 'manual' ? ' selected' : ''}>Manual</option>
                    <option value="webhook"${data.config?.event === 'webhook' ? ' selected' : ''}>Webhook</option>
                    <option value="scheduled"${data.config?.event === 'scheduled' ? ' selected' : ''}>Scheduled</option>
                </select>
            </div>
        `;
    }

    if (type === 'action') {
        const currentAction = data.actionType || '';
        let actionOptions = '<option value="">Select action...</option>';
        if (registeredActions && Object.keys(registeredActions).length) {
            for (const [key, meta] of Object.entries(registeredActions)) {
                const label = meta.label || key;
                const selected = currentAction === key ? ' selected' : '';
                actionOptions += `<option value="${escapeHtml(key)}"${selected}>${escapeHtml(label)}</option>`;
            }
        }
        html += `
            <div class="config-field">
                <label>Action Type</label>
                <select data-node-key="actionType">
                    ${actionOptions}
                </select>
            </div>
        `;

        // Render config fields for the selected action type
        if (currentAction && registeredActions?.[currentAction]?.configSchema) {
            const schema = registeredActions[currentAction].configSchema;
            for (const [fieldKey, fieldDef] of Object.entries(schema)) {
                const fieldLabel = fieldDef.label || fieldKey;
                const fieldType = fieldDef.type || 'string';
                const fieldValue = data.config?.[fieldKey] || '';
                const required = fieldDef.required ? ' required' : '';

                if (fieldType === 'object') {
                    html += `
                        <div class="config-field">
                            <label>${escapeHtml(fieldLabel)}</label>
                            <textarea data-config-key="${escapeHtml(fieldKey)}" rows="3" placeholder="JSON object"${required}>${escapeHtml(typeof fieldValue === 'object' ? JSON.stringify(fieldValue, null, 2) : String(fieldValue))}</textarea>
                        </div>
                    `;
                } else if (fieldType === 'number') {
                    html += `
                        <div class="config-field">
                            <label>${escapeHtml(fieldLabel)}</label>
                            <input type="number" data-config-key="${escapeHtml(fieldKey)}" value="${escapeHtml(String(fieldValue))}"${required}>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="config-field">
                            <label>${escapeHtml(fieldLabel)}</label>
                            <input type="text" data-config-key="${escapeHtml(fieldKey)}" value="${escapeHtml(String(fieldValue))}" placeholder="${escapeHtml(fieldLabel)}"${required}>
                        </div>
                    `;
                }
            }
        }
    }

    if (type === 'condition') {
        html += `
            <div class="config-field">
                <label>Field</label>
                <input type="text" data-config-key="field" value="${escapeHtml(data.config?.field || '')}" placeholder="record.status">
            </div>
            <div class="config-field">
                <label>Operator</label>
                <select data-config-key="operator">
                    <option value="equals"${data.config?.operator === 'equals' ? ' selected' : ''}>Equals</option>
                    <option value="not_equals"${data.config?.operator === 'not_equals' ? ' selected' : ''}>Not Equals</option>
                    <option value="contains"${data.config?.operator === 'contains' ? ' selected' : ''}>Contains</option>
                    <option value="greater_than"${data.config?.operator === 'greater_than' ? ' selected' : ''}>Greater Than</option>
                    <option value="less_than"${data.config?.operator === 'less_than' ? ' selected' : ''}>Less Than</option>
                </select>
            </div>
            <div class="config-field">
                <label>Value</label>
                <input type="text" data-config-key="value" value="${escapeHtml(data.config?.value || '')}" placeholder="active">
            </div>
        `;
    }

    if (type === 'delay') {
        html += `
            <div class="config-field">
                <label>Duration</label>
                <input type="number" data-config-key="duration" value="${escapeHtml(String(data.config?.duration || ''))}" min="0" placeholder="0">
            </div>
            <div class="config-field">
                <label>Unit</label>
                <select data-config-key="unit">
                    <option value="minutes"${data.config?.unit === 'minutes' ? ' selected' : ''}>Minutes</option>
                    <option value="hours"${data.config?.unit === 'hours' ? ' selected' : ''}>Hours</option>
                    <option value="days"${data.config?.unit === 'days' ? ' selected' : ''}>Days</option>
                </select>
            </div>
        `;
    }

    if (type === 'loop') {
        html += `
            <div class="config-field">
                <label>Collection Path</label>
                <input type="text" data-config-key="collection" value="${escapeHtml(data.config?.collection || '')}" placeholder="record.items">
            </div>
        `;
    }

    if (type === 'stop') {
        html += `
            <div class="config-field">
                <label>Reason</label>
                <input type="text" data-config-key="reason" value="${escapeHtml(data.config?.reason || '')}" placeholder="Workflow complete">
            </div>
        `;
    }

    return html;
}
