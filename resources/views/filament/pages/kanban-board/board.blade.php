<x-filament-panels::page full-height>
    <div
        x-data="kanbanBoard({
            statuses: @js($statuses->map(fn ($status) => $status['id']))
        })"
        x-on:scroll.throttle.50ms="isScrolling = true"
        x-init="$watch('isScrolling', value => { if (value) setTimeout(() => isScrolling = false, 500) })"
        class="flex overflow-x-auto overflow-y-hidden gap-x-4 pb-4 h-[calc(100vh-theme(spacing.16))] md:h-[calc(100vh-theme(spacing.44))] -mx-4 px-4 md:-mx-6 md:px-6 relative"
        :class="{ 'scrolling': isScrolling }"
        role="application"
        aria-label="Kanban board"
    >
        @foreach ($statuses as $status)
            @livewire('kanban-board.status-component', [
                'status' => $status,
                'modelClass' => $this->getModelClass(),
                'boardClass' => get_class($this)
            ], key($status['id']))
        @endforeach

        <div class="flex-shrink-0 w-4 h-1 md:w-6"></div>
    </div>

    @push('scripts')
        @vite(['resources/js/kanban-board.js'])
    @endpush
</x-filament-panels::page>

