/**
 * Shared node rendering utilities for the workflow builder.
 */

/**
 * Creates the HTML for a workflow block node.
 *
 * @param {object} data - Node data from X6
 * @param {object} options - Rendering options
 * @param {string} options.color - CSS color for the block header
 * @param {string} options.icon - SVG icon HTML
 * @param {string} options.label - Node type label
 * @param {string} [options.summary] - Config summary text
 * @returns {string} HTML string
 */
export function createNodeHTML(data, options) {
    const { color, icon, label, summary, description } = options;
    const displaySummary = summary || 'Click to configure';
    const descHtml = description
        ? `<span class="block mt-0.5 text-[11px] text-slate-400 dark:text-slate-500 truncate italic">${description}</span>`
        : '';

    // Run status badge overlay
    const runStatus = data._runStatus;
    let statusBadge = '';
    if (runStatus) {
        const statusConfig = {
            completed: { bg: '#dcfce7', text: '#15803d', label: 'Done' },
            failed: { bg: '#fee2e2', text: '#b91c1c', label: 'Failed' },
            skipped: { bg: '#f1f5f9', text: '#64748b', label: 'Skipped' },
            running: { bg: '#dbeafe', text: '#1d4ed8', label: 'Running' },
            pending: { bg: '#f1f5f9', text: '#94a3b8', label: 'Pending' },
        };
        const cfg = statusConfig[runStatus] || statusConfig.pending;
        statusBadge = `<span style="position:absolute; top:-8px; right:-8px; padding:1px 6px; font-size:10px; font-weight:600; border-radius:9999px; background:${cfg.bg}; color:${cfg.text}; box-shadow:0 1px 2px rgba(0,0,0,0.1); z-index:10; ${runStatus === 'running' ? 'animation:pulse 2s infinite;' : ''}">${cfg.label}</span>`;
    }

    return `
        <div class="wf-block" style="--block-color: ${color}; position: relative;">
            ${statusBadge}
            <div class="wf-block-header">
                <span class="wf-block-icon">${icon}</span>
                <span class="wf-block-label">${label}</span>
            </div>
            <div class="wf-block-body">
                <span class="wf-block-summary">${displaySummary}</span>
                ${descHtml}
            </div>
        </div>
    `;
}

/**
 * Standard port configurations for different node types.
 */
export const portConfigs = {
    outputOnly: {
        groups: {
            out: {
                position: 'bottom',
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
        },
        items: [{ id: 'out', group: 'out' }],
    },
    inputOutput: {
        groups: {
            in: {
                position: 'top',
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
            out: {
                position: 'bottom',
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
        },
        items: [
            { id: 'in', group: 'in' },
            { id: 'out', group: 'out' },
        ],
    },
    inputOnly: {
        groups: {
            in: {
                position: 'top',
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
        },
        items: [{ id: 'in', group: 'in' }],
    },
    conditionPorts: {
        groups: {
            in: {
                position: 'top',
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
            'out-yes': {
                position: { name: 'absolute', args: { x: '25%', y: '100%' } },
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#22c55e', strokeWidth: 1.5, fill: '#fff' },
                },
                label: { position: 'bottom' },
            },
            'out-no': {
                position: { name: 'absolute', args: { x: '75%', y: '100%' } },
                attrs: {
                    circle: { r: 5, magnet: true, stroke: '#ef4444', strokeWidth: 1.5, fill: '#fff' },
                },
                label: { position: 'bottom' },
            },
        },
        items: [
            { id: 'in', group: 'in' },
            { id: 'out-yes', group: 'out-yes' },
            { id: 'out-no', group: 'out-no' },
        ],
    },
};
