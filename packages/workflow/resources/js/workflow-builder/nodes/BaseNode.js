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
 * @param {string} [options.category] - Category pill label
 * @param {string} [options.summary] - Config summary text
 * @returns {string} HTML string
 */
/**
 * Convert a hex color to RGB components.
 */
function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
    } : { r: 100, g: 100, b: 100 }; // Neutral mid-gray fallback for invalid hex values
}

export function createNodeHTML(data, options) {
    const { color, icon, label, summary, description, category } = options;
    const displaySummary = summary || '';
    const descHtml = description
        ? `<span class="wf-block-desc">${description}</span>`
        : '';

    // Run status badge overlay (Attio-style)
    const runStatus = data._runStatus;
    let statusBadge = '';
    if (runStatus) {
        const statusConfig = {
            completed: { bg: '#dcfce7', text: '#15803d', label: 'Complete', icon: '\u2713' },
            failed: { bg: '#fee2e2', text: '#b91c1c', label: 'Failed', icon: '\u2717' },
            skipped: { bg: '#f1f5f9', text: '#64748b', label: 'Skipped', icon: '\u2014' },
            running: { bg: '#dbeafe', text: '#1d4ed8', label: 'Running', icon: '\u25CB' },
            pending: { bg: '#f1f5f9', text: '#94a3b8', label: 'Pending', icon: '\u25CB' },
        };
        const cfg = statusConfig[runStatus] || statusConfig.pending;
        statusBadge = `<span class="wf-run-badge" style="background:${cfg.bg}; color:${cfg.text}; ${runStatus === 'running' ? 'animation:pulse 2s infinite;' : ''}">${cfg.icon} ${cfg.label}</span>`;
    }

    // Category pill with muted gray background (Attio-style)
    const categoryPill = category
        ? `<span class="wf-block-category">${category}</span>`
        : '';

    // Validation badge (error or warning)
    const hasError = data._validationError;
    const hasWarning = data._warning;
    const validationBadge = hasError
        ? '<div class="wf-validation-badge wf-error-badge" title="Configuration error">!</div>'
        : hasWarning
        ? '<div class="wf-validation-badge wf-warning-badge" title="Warning">&#x26A0;</div>'
        : '';

    return `
        <div class="wf-block" style="--block-color: ${color}; position: relative;">
            ${statusBadge}
            ${validationBadge}
            <div class="wf-block-header">
                <span class="wf-block-icon-box" style="background: ${color}">${icon}</span>
                <span class="wf-block-label">${label}</span>
                ${categoryPill}
            </div>
            <div class="wf-block-body">
                ${displaySummary
                    ? `<span class="wf-block-summary">${displaySummary}</span>`
                    : `<span class="wf-block-summary wf-block-no-desc">No description</span>`
                }
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
                    circle: { r: 6, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
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
                    circle: { r: 6, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
            out: {
                position: 'bottom',
                attrs: {
                    circle: { r: 6, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
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
                    circle: { r: 6, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
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
                    circle: { r: 6, magnet: true, stroke: '#94a3b8', strokeWidth: 1.5, fill: '#fff' },
                },
            },
            'out-yes': {
                position: { name: 'absolute', args: { x: '25%', y: '100%' } },
                attrs: {
                    circle: { r: 6, magnet: true, stroke: '#22c55e', strokeWidth: 1.5, fill: '#fff' },
                },
                label: { position: 'bottom' },
            },
            'out-no': {
                position: { name: 'absolute', args: { x: '75%', y: '100%' } },
                attrs: {
                    circle: { r: 6, magnet: true, stroke: '#ef4444', strokeWidth: 1.5, fill: '#fff' },
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
