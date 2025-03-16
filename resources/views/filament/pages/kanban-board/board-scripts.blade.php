<script>
    function onStart(event) {
        document.body.classList.add("grabbing");
    }

    function onEnd(event) {
        document.body.classList.remove("grabbing");
    }

    function setData(dataTransfer, el) {
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
    }

    function onAdd(e) {
        const recordId = e.item.id;
        const statusId = e.to.dataset.statusId;
        const fromOrderedIds = [].slice.call(e.from.children).map(child => child.id);
        const toOrderedIds = [].slice.call(e.to.children).map(child => child.id);

        Livewire.dispatch('status-changed', {recordId, statusId, fromOrderedIds, toOrderedIds});
    }

    function onUpdate(e) {
        const recordId = e.item.id;
        const statusId = e.from.dataset.statusId;
        const orderedIds = [].slice.call(e.from.children).map(child => child.id);

        Livewire.dispatch('sort-changed', {recordId, statusId, orderedIds});
    }

    document.addEventListener('livewire:navigated', () => {
        const statuses = @js($statuses->map(fn ($status) => $status['id']));

        statuses.forEach(status => Sortable.create(document.querySelector(`[data-status-id='${status}']`), {
            group: 'filament-kanban',
            ghostClass: 'opacity-70',
            chosenClass: 'border-primary-400',
            dragClass: 'shadow-lg',
            animation: 200,
            easing: "cubic-bezier(1, 0, 0, 1)",
            delay: 50,
            delayOnTouchOnly: true,
            touchStartThreshold: 3,

            onStart,
            onEnd,
            onUpdate,
            setData,
            onAdd,

            // Add scroll sensitivity for better mobile experience
            scrollSpeed: 40,
            scrollSensitivity: 80,
        }));

        // Add custom scroll behavior for column scrolling
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
</script>
