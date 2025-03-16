<div class="flex flex-col w-80 flex-shrink-0 bg-gray-50 dark:bg-gray-800/30 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden animate-slide-in-right">
    <div class="bg-gray-50 dark:bg-gray-800/30 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        @livewire(
            'kanban-board.header-component',
            [
                'status' => $status,
                'modelClass' => $this->modelClass,
                'boardClass' => $this->boardClass,
            ],
            key($status['id'] . '-header')
        )
    </div>

    <div class="p-2 pt-0 flex flex-col flex-1 h-full overflow-hidden">
        <div
            data-status-id="{{ $status['id'] }}"
            class="flex flex-col gap-3 min-h-full overflow-y-auto overflow-x-hidden px-1 pt-2 pb-2 kanban-column"
        >
            @foreach ($status['records'] as $record)
                @livewire(
                    'kanban-board.record-component',
                    [
                        'record' => $record,
                        'boardClass' => $this->boardClass,
                    ],
                    key($record->getKey())
                )
            @endforeach

            <div class="h-2"></div>
        </div>
    </div>
</div>
