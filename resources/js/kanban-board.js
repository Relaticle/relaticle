document.addEventListener('alpine:init', () => {
    Alpine.data('kanbanBoard', (config) => ({
        statuses: config.statuses || [],
        isScrolling: false,
        sortableInstances: [],
        wheelEventHandlers: new Map(), // Store references to wheel event handlers
        scrollDebounceTimeout: null,

        // Constants
        SCROLL_DEBOUNCE_MS: 150,
        DRAG_ANIMATION_MS: 200,
        DRAG_DELAY_MS: 50,
        DRAG_SWAP_THRESHOLD: 0.65,
        DRAG_SCROLL_SPEED: 40,
        DRAG_SCROLL_SENSITIVITY: 80,

        init() {
            // Check if this component has already been initialized
            if (this.__initialized) {
                console.warn('Kanban board already initialized, skipping duplicate initialization');
                return;
            }

            this.__initialized = true;

            // Initialize sortables after a short delay to avoid race conditions
            setTimeout(() => {
                this.initSortables();
            }, 50);

            // Handle custom events with proper debouncing
            this.$watch('isScrolling', value => {
                if (value) {
                    clearTimeout(this.scrollDebounceTimeout);
                    this.scrollDebounceTimeout = setTimeout(() => {
                        this.isScrolling = false;
                    }, this.SCROLL_DEBOUNCE_MS);
                }
            });

            // Add custom scroll behavior for column scrolling
            this.$nextTick(() => {
                this.initColumnScrolling();
            });

            // Set up proper lifecycle hooks for Alpine.js
            this.$cleanup = () => {
                this.cleanup();
                this.__initialized = false;
            };

            // Register an event listener to handle Livewire refreshes
            window.addEventListener('livewire:update', this.handleLivewireUpdate = () => {
                // Reinitialize sortables after Livewire updates the DOM
                setTimeout(() => {
                    if (this.__initialized) {
                        this.initSortables();
                    }
                }, 100);
            });
        },

        /**
         * Initialize custom column scrolling behavior
         */
        initColumnScrolling() {
            const columns = document.querySelectorAll('.kanban-column');
            columns.forEach(column => {
                // Store the handler reference so we can remove it later
                const wheelHandler = (e) => {
                    if (e.deltaY !== 0) {
                        e.preventDefault();
                        column.scrollTop += e.deltaY;
                        this.isScrolling = true;
                    }
                };

                this.wheelEventHandlers.set(column, wheelHandler);
                column.addEventListener('wheel', wheelHandler, { passive: false });
            });
        },

        /**
         * Properly clean up all event listeners and instances
         */
        cleanup() {
            // Clean up sortable instances
            if (this.sortableInstances) {
                this.sortableInstances.forEach(instance => {
                    try {
                        if (instance && typeof instance.destroy === 'function') {
                            instance.destroy();
                        }
                    } catch (error) {
                        console.error('Error destroying sortable instance:', error);
                    }
                });
                this.sortableInstances = [];
            }

            // Clean up wheel event listeners
            if (this.wheelEventHandlers) {
                this.wheelEventHandlers.forEach((handler, element) => {
                    if (element && handler) {
                        try {
                            element.removeEventListener('wheel', handler, { passive: false });
                        } catch (error) {
                            console.error('Error removing wheel event listener:', error);
                        }
                    }
                });
                this.wheelEventHandlers.clear();
            }

            // Remove the Livewire update event listener
            if (this.handleLivewireUpdate) {
                window.removeEventListener('livewire:update', this.handleLivewireUpdate);
                this.handleLivewireUpdate = null;
            }

            // Clear any pending timeouts
            clearTimeout(this.scrollDebounceTimeout);
        },

        /**
         * Initialize Sortable.js instances for all status columns
         */
        initSortables() {
            // Clean up any existing sortable instances
            this.cleanup();

            // Create new sortable instances for each status column
            this.$nextTick(() => {
                if (!this.statuses || !this.statuses.length) {
                    console.warn('No statuses provided for kanban board initialization');
                    return;
                }

                this.statuses.forEach(statusId => {
                    const container = document.querySelector(`[data-status-id='${statusId}']`);
                    if (!container) {
                        console.warn(`Container for status "${statusId}" not found`);
                        return;
                    }

                    try {
                        const sortable = Sortable.create(container, {
                            group: 'filament-kanban',
                            ghostClass: 'opacity-70',
                            chosenClass: 'border-primary-400',
                            dragClass: 'shadow-lg',
                            animation: this.DRAG_ANIMATION_MS,
                            easing: "cubic-bezier(1, 0, 0, 1)",
                            delay: this.DRAG_DELAY_MS,
                            delayOnTouchOnly: true,
                            touchStartThreshold: 3,

                            onStart: this.onStart.bind(this),
                            onEnd: this.onEnd.bind(this),
                            onUpdate: this.onUpdate.bind(this),
                            setData: this.setData.bind(this),
                            onAdd: this.onAdd.bind(this),
                            onRemove: this.onRemove.bind(this),

                            // Better mobile experience
                            scrollSpeed: this.DRAG_SCROLL_SPEED,
                            scrollSensitivity: this.DRAG_SCROLL_SENSITIVITY,
                            fallbackOnBody: true,
                            swapThreshold: this.DRAG_SWAP_THRESHOLD
                        });

                        this.sortableInstances.push(sortable);
                    } catch (error) {
                        console.error(`Failed to initialize sortable for status "${statusId}":`, error);
                    }
                });
            });
        },

        /**
         * Handle drag start event
         * @param {Object} event - Sortable.js event object
         */
        onStart(event) {
            document.body.classList.add("grabbing");
        },

        /**
         * Handle drag end event
         * @param {Object} event - Sortable.js event object
         */
        onEnd(event) {
            document.body.classList.remove("grabbing");
        },

        /**
         * Set data for drag and drop operations
         * @param {DataTransfer} dataTransfer - HTML5 DataTransfer object
         * @param {HTMLElement} el - Element being dragged
         */
        setData(dataTransfer, el) {
            if (!dataTransfer || !el) return;

            dataTransfer.setData('id', el.id);

            // Create a custom drag ghost with better performance
            const rect = el.getBoundingClientRect();
            const ghost = el.cloneNode(true);

            // Apply styles directly instead of multiple style property changes
            Object.assign(ghost.style, {
                width: `${rect.width}px`,
                transform: 'rotate(2deg)',
                position: 'absolute',
                top: '-1000px',
                opacity: '0.8',
                pointerEvents: 'none'
            });

            document.body.appendChild(ghost);
            dataTransfer.setDragImage(ghost, 20, 20);

            // Clean up the ghost element after dragging
            requestAnimationFrame(() => {
                document.body.removeChild(ghost);
            });
        },

        /**
         * Handle removing an item from a column
         * @param {Object} e - Sortable.js event object
         */
        onRemove(e) {
            try {
                if (!e || !e.from || !e.from.dataset) return;

                const fromStatusId = e.from.dataset.statusId;
                // Dispatch an event to notify the column that a record was removed
                window.dispatchEvent(new CustomEvent('record-removed', {
                    detail: { statusId: fromStatusId }
                }));
            } catch (error) {
                console.error('Error in onRemove handler:', error);
                this.handleDragError('remove', error);
            }
        },

        /**
         * Handle adding an item to a column
         * @param {Object} e - Sortable.js event object
         */
        onAdd(e) {
            try {
                if (!e || !e.item || !e.to || !e.from) return;

                const recordId = e.item.id;
                const statusId = e.to.dataset.statusId;
                const fromOrderedIds = Array.from(e.from.children).map(child => child.id);
                const toOrderedIds = Array.from(e.to.children).map(child => child.id);

                // Dispatch an event to notify the column that a record was added
                window.dispatchEvent(new CustomEvent('record-added', {
                    detail: { statusId: statusId }
                }));

                Livewire.dispatch('status-changed', {
                    recordId,
                    statusId,
                    fromOrderedIds,
                    toOrderedIds
                });
            } catch (error) {
                console.error('Error in onAdd handler:', error);
                this.handleDragError('add', error);
            }
        },

        /**
         * Handle reordering items within a column
         * @param {Object} e - Sortable.js event object
         */
        onUpdate(e) {
            try {
                if (!e || !e.item || !e.from) return;

                const recordId = e.item.id;
                const statusId = e.from.dataset.statusId;
                const orderedIds = Array.from(e.from.children).map(child => child.id);

                Livewire.dispatch('sort-changed', {
                    recordId,
                    statusId,
                    orderedIds
                });
            } catch (error) {
                console.error('Error in onUpdate handler:', error);
                this.handleDragError('update', error);
            }
        },

        /**
         * Handle errors during drag and drop operations
         * @param {string} operation - The operation that failed (add, remove, update)
         * @param {Error} error - The error that occurred
         */
        handleDragError(operation, error) {
            // Log the error
            console.error(`Kanban board error during ${operation} operation:`, error);

            // Notify Livewire about the error
            Livewire.dispatch('kanban-error', {
                message: `Error during ${operation} operation`,
                operation,
                errorMessage: error.message
            });

            // Optionally refresh the board on critical errors
            if (operation === 'add') {
                // Consider a full refresh for critical errors
                // Livewire.dispatch('refresh-kanban-board');
            }
        }
    }));

    Alpine.data('kanbanRecord', (config = {}) => ({
        animating: false,
        animationDuration: 1000, // Move magic number to a property

        init() {
            if (config.isNew) {
                this.animateNewRecord();
            }
        },

        /**
         * Animate a newly added record
         */
        animateNewRecord() {
            this.animating = true;
            setTimeout(() => {
                this.animating = false;
            }, this.animationDuration);
        },

        /**
         * Clean up when element is disconnected
         */
        destroy() {
            // Any cleanup needed for individual records
        }
    }));
});
