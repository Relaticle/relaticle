<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})"
    class="record border bg-gray-100 dark:bg-gray-700 rounded-lg px-4 py-2 cursor-grab font-medium text-gray-600 dark:text-gray-200"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}) < 3)
        x-data
        x-init="
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
    @endif
>
    {{ $record->{static::$recordTitleAttribute} }}
</div>
