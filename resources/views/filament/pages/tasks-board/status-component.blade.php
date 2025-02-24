<div class="md:w-[24rem] flex-shrink-0 md:min-h-full overflow-y-hidden flex flex-col px-0.5">
    @livewire('tasks-board.header-component', ['status' => $status], key($status['id']))

    <x-filament::section compact>
        <div
            data-status-id="{{ $status['id'] }}"
            class="flex flex-col flex-1 space-y-3 h-screen"
        >
            @foreach($status['records'] as $record)
                @livewire('tasks-board.record-component', ['task' => $record], key($record->getKey()))
            @endforeach
        </div>
    </x-filament::section>
</div>
