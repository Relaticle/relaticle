/**
 * Alpine.js Config Panel Component
 *
 * Manages the right-panel node configuration when a node is selected.
 * Listens for custom events from the X6 graph.
 */
export function configPanelComponent() {
    return {
        nodeData: null,
        selectedNodeId: null,
        registeredActions: null,

        init() {
            // Listen for node selection events from X6
            window.addEventListener('wf:node-selected', (e) => {
                this.nodeData = e.detail.data;
                this.selectedNodeId = e.detail.nodeId;
                this.renderConfigForm();
            });

            window.addEventListener('wf:node-deselected', () => {
                this.nodeData = null;
                this.selectedNodeId = null;
            });
        },

        async renderConfigForm() {
            if (!this.nodeData) return;

            const body = document.getElementById('config-panel-body');
            if (!body) return;

            if (!this.registeredActions) {
                await this.fetchActions();
            }

            body.innerHTML = this.buildConfigHTML(this.nodeData, this.registeredActions || {});
            this.bindFormHandlers(body);
        },

        async fetchActions() {
            try {
                const workflowId = this.$root?.dataset?.workflowId || document.querySelector('[x-data]')?.dataset?.workflowId;
                if (!workflowId) return;
                const resp = await fetch(`/workflow/api/workflows/${workflowId}/canvas`);
                if (!resp.ok) return;
                const data = await resp.json();
                this.registeredActions = data.meta?.registered_actions || {};
            } catch {
                this.registeredActions = {};
            }
        },

        bindFormHandlers(container) {
            container.querySelectorAll('input, select, textarea').forEach((input) => {
                input.addEventListener('change', () => {
                    this.saveFieldsToNode(container);
                    // Re-render if action type changed
                    if (input.dataset.nodeKey === 'actionType') {
                        this.renderConfigForm();
                    }
                });
            });

            // Variable picker {x} buttons
            container.querySelectorAll('.wf-var-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const configKey = btn.dataset.varFor;
                    const input = container.querySelector(`[data-config-key="${configKey}"]`);
                    if (input && this.selectedNodeId) {
                        this.openVariablePicker(input, this.selectedNodeId);
                    }
                });
            });
        },

        saveFieldsToNode(container) {
            if (!this.selectedNodeId) return;

            const graph = window.__wfGraph;
            if (!graph) return;

            const node = graph.getCellById(this.selectedNodeId);
            if (!node) return;

            const currentData = node.getData() || {};
            const updated = { ...currentData };
            const config = { ...(currentData.config || {}) };

            container.querySelectorAll('[data-config-key]').forEach((el) => {
                config[el.dataset.configKey] = el.value;
            });
            updated.config = config;

            // Handle action_type separately
            const actionTypeEl = container.querySelector('[data-node-key="actionType"]');
            if (actionTypeEl) {
                updated.actionType = actionTypeEl.value;
            }

            node.setData(updated, { overwrite: true });
            this.nodeData = updated;
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str || ''));
            return div.innerHTML;
        },

        _wrapWithVarButton(inputHtml, configKey) {
            return `
                <div style="position: relative;">
                    ${inputHtml}
                    <button type="button" class="wf-var-btn" data-var-for="${configKey}" title="Insert variable">{x}</button>
                </div>
            `;
        },

        buildConfigHTML(data, registeredActions) {
            const type = data.type || 'unknown';
            const esc = (s) => this.escapeHtml(s);
            let html = `
                <div class="wf-config-group">
                    <label>Type</label>
                    <input type="text" value="${esc(type)}" disabled>
                </div>
                <div class="wf-config-group">
                    <label>Description</label>
                    <input type="text" data-config-key="description" value="${esc(data.config?.description || '')}" placeholder="Add a description..." class="wf-config-description">
                </div>
            `;

            if (type === 'trigger') {
                html += `
                    <div class="wf-config-group">
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
                for (const [key, meta] of Object.entries(registeredActions)) {
                    const label = meta.label || key;
                    const selected = currentAction === key ? ' selected' : '';
                    actionOptions += `<option value="${esc(key)}"${selected}>${esc(label)}</option>`;
                }
                html += `
                    <div class="wf-config-group">
                        <label>Action Type</label>
                        <select data-node-key="actionType">${actionOptions}</select>
                    </div>
                `;

                if (currentAction && registeredActions?.[currentAction]?.configSchema) {
                    const schema = registeredActions[currentAction].configSchema;
                    for (const [fieldKey, fieldDef] of Object.entries(schema)) {
                        const fieldLabel = fieldDef.label || fieldKey;
                        const fieldType = fieldDef.type || 'string';
                        const fieldValue = data.config?.[fieldKey] || '';
                        const required = fieldDef.required ? ' required' : '';

                        if (fieldType === 'object') {
                            html += `
                                <div class="wf-config-group">
                                    <label>${esc(fieldLabel)}</label>
                                    ${this._wrapWithVarButton(`<textarea data-config-key="${esc(fieldKey)}" rows="3" placeholder="JSON object"${required}>${esc(typeof fieldValue === 'object' ? JSON.stringify(fieldValue, null, 2) : String(fieldValue))}</textarea>`, esc(fieldKey))}
                                </div>
                            `;
                        } else if (fieldType === 'number' || fieldType === 'integer') {
                            html += `
                                <div class="wf-config-group">
                                    <label>${esc(fieldLabel)}</label>
                                    <input type="number" data-config-key="${esc(fieldKey)}" value="${esc(String(fieldValue))}"${required}>
                                </div>
                            `;
                        } else if (fieldType === 'select') {
                            const options = fieldDef.options || [];
                            let optionsHtml = '';
                            options.forEach(opt => {
                                optionsHtml += `<option value="${esc(opt)}"${fieldValue === opt ? ' selected' : ''}>${esc(opt)}</option>`;
                            });
                            html += `
                                <div class="wf-config-group">
                                    <label>${esc(fieldLabel)}</label>
                                    <select data-config-key="${esc(fieldKey)}"${required}>${optionsHtml}</select>
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="wf-config-group">
                                    <label>${esc(fieldLabel)}</label>
                                    ${this._wrapWithVarButton(`<input type="text" data-config-key="${esc(fieldKey)}" value="${esc(String(fieldValue))}" placeholder="${esc(fieldLabel)}"${required}>`, esc(fieldKey))}
                                </div>
                            `;
                        }
                    }
                }
            }

            if (type === 'condition') {
                html += `
                    <div class="wf-config-group">
                        <label>Field</label>
                        ${this._wrapWithVarButton(`<input type="text" data-config-key="field" value="${esc(data.config?.field || '')}" placeholder="record.status">`, 'field')}
                    </div>
                    <div class="wf-config-group">
                        <label>Operator</label>
                        <select data-config-key="operator">
                            <option value="equals"${data.config?.operator === 'equals' ? ' selected' : ''}>Equals</option>
                            <option value="not_equals"${data.config?.operator === 'not_equals' ? ' selected' : ''}>Not Equals</option>
                            <option value="contains"${data.config?.operator === 'contains' ? ' selected' : ''}>Contains</option>
                            <option value="greater_than"${data.config?.operator === 'greater_than' ? ' selected' : ''}>Greater Than</option>
                            <option value="less_than"${data.config?.operator === 'less_than' ? ' selected' : ''}>Less Than</option>
                        </select>
                    </div>
                    <div class="wf-config-group">
                        <label>Value</label>
                        ${this._wrapWithVarButton(`<input type="text" data-config-key="value" value="${esc(data.config?.value || '')}" placeholder="active">`, 'value')}
                    </div>
                `;
            }

            if (type === 'delay') {
                html += `
                    <div class="wf-config-group">
                        <label>Duration</label>
                        <input type="number" data-config-key="duration" value="${esc(String(data.config?.duration || ''))}" min="0">
                    </div>
                    <div class="wf-config-group">
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
                    <div class="wf-config-group">
                        <label>Collection Path</label>
                        ${this._wrapWithVarButton(`<input type="text" data-config-key="collection" value="${esc(data.config?.collection || '')}" placeholder="record.items">`, 'collection')}
                    </div>
                `;
            }

            if (type === 'stop') {
                html += `
                    <div class="wf-config-group">
                        <label>Reason</label>
                        ${this._wrapWithVarButton(`<input type="text" data-config-key="reason" value="${esc(data.config?.reason || '')}" placeholder="Workflow complete">`, 'reason')}
                    </div>
                `;
            }

            return html;
        },
    };
}
