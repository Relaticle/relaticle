<x-filament-panels::page full-height>
    <div x-data wire:ignore.self class="md:flex overflow-x-auto overflow-y-hidden gap-x-4 ">
        @foreach($statuses as $status)
            @include(static::$statusView)
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>

    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless
</x-filament-panels::page>
