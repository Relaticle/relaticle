<div id="{{ $task->getKey() }}">
    <div
        wire:click="mountAction('edit')"
        class="record border bg-gray-100 dark:bg-gray-700 rounded-lg px-4 py-2 cursor-grab font-medium text-gray-600 dark:text-gray-200"
        @if($task->timestamps && now()->diffInSeconds($task->{$task::UPDATED_AT}) < 3)
            x-data
        x-init="
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
        @endif
    >
        {{ $task->title }}
    </div>

    <x-filament-actions::modals/>
</div>
