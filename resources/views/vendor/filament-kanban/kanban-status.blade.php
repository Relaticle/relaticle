@props(['status'])


<div class="md:w-[24rem] flex-shrink-0 md:min-h-full overflow-y-hidden flex flex-col px-0.5">
    @include(static::$headerView)

    <x-filament::section compact>
    <div
        data-status-id="{{ $status['id'] }}"
        class="flex flex-col flex-1 h-screen"
    >
        @foreach($status['records'] as $record)
            @include(static::$recordView)
        @endforeach
    </div>
    </x-filament::section>

</div>
