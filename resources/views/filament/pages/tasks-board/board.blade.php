<x-filament-panels::page full-height>
    <div x-data wire:ignore.self class="md:flex overflow-x-auto overflow-y-hidden gap-x-4 ">
        @foreach($statuses as $status)
            @livewire('tasks-board.status-component', ['status' => $status], key($status['id']))
        @endforeach


        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>
</x-filament-panels::page>
