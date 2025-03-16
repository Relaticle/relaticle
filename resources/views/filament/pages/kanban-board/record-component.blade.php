<div id="{{ $record->getKey() }}" class="group relative">
    <div
        wire:click="mountAction('edit')"
        class="record border bg-white dark:bg-gray-700/60 rounded-md px-3 py-2.5 cursor-grab font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:shadow transition-all duration-200 transform hover:-translate-y-0.5 group-hover:border-primary-300 dark:group-hover:border-primary-700"
        @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}) < 3)
            x-data
            x-init="
                // Animation code removed
            "
        @endif
    >
        <div class="flex items-start justify-between">
            <div class="flex-1 break-words">
                {{ $record->{$this->titleAttribute()} }}
            </div>

            <button type="button" class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400">
                <x-heroicon-m-pencil-square class="w-4 h-4" />
            </button>
        </div>
    </div>

    <x-filament-actions::modals/>
</div>
