{{-- Step 2: Map Columns --}}
<div class="space-y-6" x-data="{ hovered: '{{ $this->csvHeaders[0] ?? '' }}' }" wire:ignore.self>
    <div class="flex gap-6">
        <div class="flex-1">
            <div class="flex items-center pb-2 mb-1 text-xs font-medium text-gray-500 uppercase">
                <div class="flex-1">File column</div><div class="w-6"></div><div class="flex-1">Attribute</div>
            </div>
            @foreach ($this->csvHeaders as $header)
                @php $mapped = array_search($header, $this->columnMap); @endphp
                <div wire:key="map-{{ md5($header) }}" class="flex items-center py-2 px-2 -mx-2 rounded-lg" :class="hovered === '{{ addslashes($header) }}' ? 'bg-primary-50 dark:bg-primary-950/30' : ''" @mouseenter="hovered = '{{ addslashes($header) }}'">
                    <div class="flex-1 text-sm text-gray-950 dark:text-white">{{ $header }}</div>
                    <div class="w-6 flex justify-center"><x-filament::icon icon="heroicon-m-arrow-right" class="h-3.5 w-3.5 text-gray-300" /></div>
                    <div class="flex-1">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:change="mapCsvColumnToField('{{ addslashes($header) }}', $event.target.value)">
                                <option value="" @selected($mapped === false)>Select attribute</option>
                                @foreach ($this->importerColumns as $col)
                                    <option value="{{ $col->getName() }}" @selected($mapped === $col->getName()) @disabled(!empty($this->columnMap[$col->getName()]) && $this->columnMap[$col->getName()] !== $header)>
                                        {{ $col->getLabel() }}{{ $col->isMappingRequired() ? ' *' : '' }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="w-72 shrink-0 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden flex flex-col">
            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                <span class="text-sm font-medium" x-text="hovered"></span>
                <span class="text-xs text-gray-400">Preview</span>
            </div>
            <div class="flex-1 overflow-y-auto">
                @foreach ($this->csvHeaders as $header)
                    <div x-show="hovered === '{{ addslashes($header) }}'" x-cloak>
                        @foreach ($this->getColumnPreviewValues($header, 5) as $v)
                            <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-300">{{ $v ?: '(blank)' }}</div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @php $unmapped = collect($this->importerColumns)->filter(fn($c) => $c->isMappingRequired() && empty($this->columnMap[$c->getName()]))->pluck('label'); @endphp
    @if ($unmapped->isNotEmpty())
        <div class="flex items-start gap-2 p-3 rounded-lg bg-warning-50 dark:bg-warning-950/50 border border-warning-200 dark:border-warning-800">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-warning-500" />
            <span class="text-sm"><strong>Required:</strong> {{ $unmapped->join(', ') }}</span>
        </div>
    @endif
</div>
