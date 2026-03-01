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

        async publishWorkflow() {
            if (this.publishing) return;
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
                    this.showToast('Workflow published', 'success');
                } else {
                    const data = await res.json();
                    this.showToast(data.errors?.join(', ') || 'Publish failed', 'error');
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
