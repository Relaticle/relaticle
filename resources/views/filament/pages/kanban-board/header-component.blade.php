<div class="p-3">
    <div class="flex items-center justify-between">
        <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-500"></span>
            <span>{{ $status['name'] }}</span>
            <span x-text="'(' + recordCount + ')'" class="text-sm text-gray-500 dark:text-gray-400 ml-1 transition-all duration-200"></span>
        </h3>

        <div>
            {{ $this->createAction() }}
        </div>
    </div>

    <x-filament-actions::modals/>
</div>
