<script>
    function onStart() {
        setTimeout(() => document.body.classList.add("grabbing"))
    }

    function onEnd() {
        document.body.classList.remove("grabbing")
    }

    function setData(dataTransfer, el) {
        dataTransfer.setData('id', el.id)
    }

    function onAdd(e) {
        const recordId = e.item.id

        console.log(e, recordId)
        const statusId = e.to.dataset.statusId
        const fromOrderedIds = [].slice.call(e.from.children).map(child => child.id)
        const toOrderedIds = [].slice.call(e.to.children).map(child => child.id)

        Livewire.dispatch('status-changed', {recordId, statusId, fromOrderedIds, toOrderedIds})
    }

    function onUpdate(e) {
        const recordId = e.item.id
        const statusId = e.from.dataset.statusId
        const orderedIds = [].slice.call(e.from.children).map(child => child.id)

        Livewire.dispatch('sort-changed', {recordId, statusId, orderedIds})
    }

    document.addEventListener('livewire:navigated', () => {
        const statuses = @js($statuses->map(fn ($status) => $status['id']))

        statuses.forEach(status => Sortable.create(document.querySelector(`[data-status-id='${status}']`), {
            group: 'filament-kanban',
            ghostClass: 'opacity-50',
            animation: 150,

            onStart,
            onEnd,
            onUpdate,
            setData,
            onAdd,
        }))
    })
</script>
