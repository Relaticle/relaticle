export function initConfigPanel(graph) {
    const panel = document.getElementById('config-panel');
    const body = document.getElementById('config-panel-body');
    const closeBtn = document.getElementById('config-panel-close');

    closeBtn?.addEventListener('click', () => {
        panel.style.display = 'none';
    });

    graph.on('node:click', ({ node }) => {
        const data = node.getData() || {};
        body.innerHTML = renderConfig(data);
        panel.style.display = 'block';

        // Bind change handlers to save config back to node
        body.querySelectorAll('input, select, textarea').forEach((input) => {
            input.addEventListener('change', () => {
                const config = {};
                body.querySelectorAll('[data-config-key]').forEach((el) => {
                    config[el.dataset.configKey] = el.value;
                });
                node.setData({ ...data, config }, { overwrite: true });
            });
        });
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

function renderConfig(data) {
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
        html += `
            <div class="config-field">
                <label>Action Type</label>
                <select data-config-key="action_type">
                    <option value="">Select action...</option>
                    <option value="send_email"${data.config?.action_type === 'send_email' ? ' selected' : ''}>Send Email</option>
                    <option value="send_webhook"${data.config?.action_type === 'send_webhook' ? ' selected' : ''}>Send Webhook</option>
                    <option value="http_request"${data.config?.action_type === 'http_request' ? ' selected' : ''}>HTTP Request</option>
                </select>
            </div>
        `;
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
