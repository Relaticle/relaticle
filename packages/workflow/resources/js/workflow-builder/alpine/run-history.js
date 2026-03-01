/**
 * Alpine.js Run History Component
 *
 * Manages the run history panel: listing runs, viewing run details,
 * and entering/exiting run visualization mode on the canvas.
 */

export function runHistoryComponent(workflowId) {
    return {
        runs: [],
        selectedRun: null,
        runViewActive: false,
        loadingRuns: false,

        init() {
            this.loadRuns();

            // Listen for close-panel event
            this.$el.addEventListener('close-panel', () => {
                if (this.runViewActive) {
                    this.exitRunView();
                }
            });
        },

        async loadRuns() {
            this.loadingRuns = true;
            try {
                const res = await fetch(`/workflow/api/workflows/${workflowId}/runs`);
                if (res.ok) {
                    const data = await res.json();
                    this.runs = data.runs || [];
                }
            } catch (e) {
                console.warn('Failed to load runs:', e);
            } finally {
                this.loadingRuns = false;
            }
        },

        async selectRun(runId) {
            try {
                const res = await fetch(`/workflow/api/workflow-runs/${runId}`);
                if (res.ok) {
                    const data = await res.json();
                    this.selectedRun = data.run;
                    this.runViewActive = true;

                    // Dispatch event for canvas to enter run view mode
                    window.dispatchEvent(new CustomEvent('wf:enter-run-view', {
                        detail: data.run,
                    }));
                }
            } catch (e) {
                console.warn('Failed to load run details:', e);
            }
        },

        exitRunView() {
            this.runViewActive = false;
            this.selectedRun = null;

            // Dispatch event for canvas to exit run view mode
            window.dispatchEvent(new CustomEvent('wf:exit-run-view'));
        },

        getNodeLabel(nodeId) {
            const graph = window.__wfGraph;
            if (!graph) return nodeId;
            const cell = graph.getCellById(nodeId);
            if (!cell) return nodeId;
            const data = cell.getData() || {};
            const type = data.type || 'unknown';
            const actionType = data.actionType;
            if (actionType) {
                return actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }
            return type.charAt(0).toUpperCase() + type.slice(1);
        },

        formatDuration(start, end) {
            if (!start || !end) return '';
            const ms = new Date(end) - new Date(start);
            if (ms < 1000) return `${ms}ms`;
            if (ms < 60000) return `${Math.round(ms / 1000)}s`;
            return `${Math.round(ms / 60000)}m`;
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
}
