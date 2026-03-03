/**
 * Alpine.js Top Bar Component
 *
 * Manages workflow name editing, lifecycle controls (publish/pause/archive),
 * and status display. Mixed into the root workflowBuilder component.
 */

/**
 * Returns top bar methods and state to be spread into the root Alpine component.
 */
export function topBarMixin() {
    return {
        editingName: false,
        publishing: false,
        workflowDescription: '',
        triggerType: '',
        maxStepsPerRun: 100,
        notifyOnFailure: false,

        async saveName() {
            if (!this.workflowName || !this.workflowId) return;
            try {
                // Save name via canvas update — the name is saved separately
                // We'll use a simple PATCH approach if available, or save with canvas
                await fetch(`/workflow/api/workflows/${this.workflowId}/canvas`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ name: this.workflowName }),
                });
            } catch (e) {
                console.warn('Failed to save workflow name:', e);
            }
        },

        async saveDescription() {
            if (!this.workflowId) return;
            try {
                await fetch(`/workflow/api/workflows/${this.workflowId}/canvas`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ description: this.workflowDescription }),
                });
            } catch (e) {
                console.warn('Failed to save description:', e);
            }
        },

        async publishWorkflow() {
            if (this.publishing) return;

            // Client-side validation first
            const graph = window.__wfGraph;
            if (graph) {
                const { validateAllNodes } = await import('../config-panel.js');
                const { errors, warnings } = validateAllNodes(graph, true);
                if (errors.length > 0) {
                    const messages = errors.map(e => e.message);
                    this.showToast(`Cannot publish: ${messages[0]}${errors.length > 1 ? ` (+${errors.length - 1} more)` : ''}`, 'error');
                    return;
                }
                if (warnings.length > 0) {
                    this.showToast(`${warnings.length} warning(s): ${warnings[0].message}${warnings.length > 1 ? ` (+${warnings.length - 1} more)` : ''}`, 'warning');
                }
            }

            this.publishing = true;

            try {
                const res = await fetch(`/workflow/api/workflows/${this.workflowId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });

                if (res.ok) {
                    this.workflowStatus = 'live';
                    this.showToast('Workflow published successfully', 'success');
                } else {
                    const data = await res.json();
                    const errorList = data.errors || [];
                    if (errorList.length > 0) {
                        errorList.forEach((err, i) => {
                            setTimeout(() => this.showToast(err, 'error'), i * 200);
                        });
                    } else {
                        this.showToast(data.message || 'Publish failed', 'error');
                    }
                }
            } catch (e) {
                this.showToast('Publish failed: ' + e.message, 'error');
            } finally {
                this.publishing = false;
            }
        },

        async pauseWorkflow() {
            try {
                const res = await fetch(`/workflow/api/workflows/${this.workflowId}/pause`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });

                if (res.ok) {
                    this.workflowStatus = 'paused';
                    this.showToast('Workflow paused', 'warning');
                } else {
                    const data = await res.json();
                    this.showToast(data.errors?.join(', ') || 'Pause failed', 'error');
                }
            } catch (e) {
                this.showToast('Pause failed: ' + e.message, 'error');
            }
        },

        async saveSettings() {
            if (!this.workflowId) return;
            try {
                await fetch(`/workflow/api/workflows/${this.workflowId}/canvas`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        settings: {
                            max_steps: parseInt(this.maxStepsPerRun, 10) || 100,
                            notify_on_failure: this.notifyOnFailure,
                        },
                    }),
                });
                this.showToast('Settings saved', 'success');
            } catch (e) {
                console.warn('Failed to save settings:', e);
                this.showToast('Failed to save settings', 'error');
            }
        },

        async archiveWorkflow() {
            try {
                const res = await fetch(`/workflow/api/workflows/${this.workflowId}/archive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });

                if (res.ok) {
                    this.workflowStatus = 'archived';
                    this.showToast('Workflow archived', 'warning');
                } else {
                    const data = await res.json();
                    this.showToast(data.errors?.join(', ') || 'Archive failed', 'error');
                }
            } catch (e) {
                this.showToast('Archive failed: ' + e.message, 'error');
            }
        },
    };
}
