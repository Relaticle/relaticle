{{-- Step 1: Upload --}}
<div class="space-y-6">
    @if ($this->persistedFilePath && $this->csvHeaders)
        <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-12 flex items-center justify-center min-h-[300px]">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 w-full max-w-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $this->uploadedFile?->getClientOriginalName() ?? 'import.csv' }}</p>
                        <p class="text-xs text-gray-500">{{ Number::fileSize($this->uploadedFile?->getSize() ?? 0, precision: 1) }}</p>
                    </div>
                    <button type="button" wire:click="removeFile" class="p-1.5 rounded-lg text-gray-400 hover:text-danger-500">
                        <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                    </button>
                </div>
                <div class="border-t border-dashed border-gray-200 dark:border-gray-700 my-4"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Columns</p>
                        <p class="text-xl font-semibold text-gray-950 dark:text-white">{{ count($this->csvHeaders) }}</p>
                    </div>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Rows</p>
                        <p class="text-xl font-semibold text-gray-950 dark:text-white">{{ number_format($this->rowCount) }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div x-data="{ dragging: false }" x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false" x-on:drop.prevent="dragging = false"
            :class="dragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-900'"
            class="relative rounded-xl border border-dashed p-12 text-center min-h-[300px] flex flex-col items-center justify-center">
            <input type="file" wire:model="uploadedFile" accept=".csv,.xlsx,.xls,.ods,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-12 w-12 text-gray-400" />
            <p class="mt-6 text-sm text-gray-700 dark:text-gray-400">Drop your .CSV or .XLSX file here</p>
            <x-filament::button color="gray" class="mt-6 pointer-events-none">Choose a file</x-filament::button>
            @error('uploadedFile')<p class="mt-4 text-sm text-danger-600">{{ $message }}</p>@enderror
        </div>
    @endif
</div>
