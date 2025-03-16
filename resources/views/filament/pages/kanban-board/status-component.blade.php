<div class="md:w-[24rem] flex-shrink-0 md:min-h-full overflow-y-hidden flex flex-col px-0.5">
    @livewire(
        'kanban-board.header-component',
        [
            'status' => $status,
            'modelClass' => $this->modelClass,
            'boardClass' => $this->boardClass,
        ],
        key($status['id'] . '-header')
    )
    <x-filament::section compact>
        <div data-status-id="{{ $status['id'] }}" class="flex flex-col flex-1 space-y-3 h-screen">
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
        </div>
    </x-filament::section>
</div>
