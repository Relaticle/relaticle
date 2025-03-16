// Initialize sortables on page load/navigation
document.addEventListener('livewire:navigated', () => {
    // Initialize or reinitialize the Alpine.js components
    if (Alpine && Alpine.initTree) {
        Alpine.initTree(document.body);
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('kanbanBoard', (config) => ({
        statuses: config.statuses || [],
        isScrolling: false,
        sortableInstances: [],

        init() {
            this.initSortables();

            // Handle custom events
            this.$watch('isScrolling', value => {
                if (value) {
                    setTimeout(() => this.isScrolling = false, 150);
                }
            });

            // Add custom scroll behavior for column scrolling
            this.$nextTick(() => {
                const columns = document.querySelectorAll('.kanban-column');
                columns.forEach(column => {
                    column.addEventListener('wheel', (e) => {
                        if (e.deltaY !== 0) {
                            e.preventDefault();
                            column.scrollTop += e.deltaY;
                        }
                    }, { passive: false });
                });
            });
        },

        initSortables() {
            // Clean up any existing sortable instances
            this.sortableInstances.forEach(instance => instance.destroy());
            this.sortableInstances = [];

            // Create new sortable instances for each status column
            this.$nextTick(() => {
                this.statuses.forEach(statusId => {
                    const container = document.querySelector(`[data-status-id='${statusId}']`);
                    if (container) {
                        const sortable = Sortable.create(container, {
                            group: 'filament-kanban',
                            ghostClass: 'opacity-70',
                            chosenClass: 'border-primary-400',
                            dragClass: 'shadow-lg',
                            animation: 200,
                            easing: "cubic-bezier(1, 0, 0, 1)",
                            delay: 50,
                            delayOnTouchOnly: true,
                            touchStartThreshold: 3,

                            onStart: this.onStart.bind(this),
                            onEnd: this.onEnd.bind(this),
                            onUpdate: this.onUpdate.bind(this),
                            setData: this.setData.bind(this),
                            onAdd: this.onAdd.bind(this),
                            onRemove: this.onRemove.bind(this),

                            // Better mobile experience
                            scrollSpeed: 40,
                            scrollSensitivity: 80,
                            fallbackOnBody: true,
                            swapThreshold: 0.65
                        });

                        this.sortableInstances.push(sortable);
                    }
                });
            });
        },

        onStart(event) {
            document.body.classList.add("grabbing");
        },

        onEnd(event) {
            document.body.classList.remove("grabbing");
        },

        setData(dataTransfer, el) {
            dataTransfer.setData('id', el.id);

            // Create a custom drag ghost
            const ghost = el.cloneNode(true);
            ghost.style.width = `${el.offsetWidth}px`;
            ghost.style.transform = 'rotate(2deg)';
            ghost.style.position = 'absolute';
            ghost.style.top = '-1000px';
            ghost.style.opacity = '0.8';
            document.body.appendChild(ghost);
            dataTransfer.setDragImage(ghost, 20, 20);

            // Clean up the ghost element after dragging
            setTimeout(() => {
                document.body.removeChild(ghost);
            }, 0);
        },

        onRemove(e) {
            try {
                const fromStatusId = e.from.dataset.statusId;
                // Dispatch an event to notify the column that a record was removed
                window.dispatchEvent(new CustomEvent('record-removed', {
                    detail: { statusId: fromStatusId }
                }));
            } catch (error) {
                console.error('Error in onRemove handler:', error);
            }
        },

        onAdd(e) {
            try {
                const recordId = e.item.id;
                const statusId = e.to.dataset.statusId;
                const fromOrderedIds = Array.from(e.from.children).map(child => child.id);
                const toOrderedIds = Array.from(e.to.children).map(child => child.id);

                // Dispatch an event to notify the column that a record was added
                window.dispatchEvent(new CustomEvent('record-added', {
                    detail: { statusId: statusId }
                }));

                Livewire.dispatch('status-changed', {recordId, statusId, fromOrderedIds, toOrderedIds});
            } catch (error) {
                console.error('Error in onAdd handler:', error);
                // Potential recovery by refreshing the board
                // Livewire.dispatch('kanban-error', { message: 'Error during card movement' });
            }
        },

        onUpdate(e) {
            try {
                const recordId = e.item.id;
                const statusId = e.from.dataset.statusId;
                const orderedIds = Array.from(e.from.children).map(child => child.id);

                Livewire.dispatch('sort-changed', {recordId, statusId, orderedIds});
            } catch (error) {
                console.error('Error in onUpdate handler:', error);
            }
        }
    }));

    Alpine.data('kanbanRecord', (config = {}) => ({
        animating: false,

        init() {
            if (config.isNew) {
                this.animateNewRecord();
            }
        },

        animateNewRecord() {
            this.animating = true;
            setTimeout(() => {
                this.animating = false;
            }, 1000);
        }
    }));
});
