<div>
    {{-- Step Progress --}}
    <nav class="mb-8">
        <ol class="flex items-center gap-2">
            @foreach ([1 => 'Upload', 2 => 'Map Columns', 3 => 'Review', 4 => 'Preview'] as $step => $label)
                <li class="flex items-center">
                    <button type="button" wire:click="goToStep({{ $step }})" @disabled($step > $currentStep)
                        @class(['flex items-center gap-2 text-sm', 'cursor-pointer hover:opacity-80' => $step <= $currentStep, 'cursor-default' => $step > $currentStep])>
                        <span @class(['inline-flex items-center justify-center h-5 w-5 rounded text-xs font-medium', 'bg-primary-600 text-white' => $currentStep === $step, 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $currentStep !== $step])>{{ $step }}</span>
                        <span @class(['text-gray-950 dark:text-white' => $currentStep === $step, 'text-gray-500 dark:text-gray-400' => $currentStep !== $step])>{{ $label }}</span>
                    </button>
                    @if ($step < 4)
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4 text-gray-300 dark:text-gray-600 mx-2" />
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    {{-- Step 1: Upload --}}
    @if ($currentStep === 1)
        <div class="space-y-6">
            @if ($persistedFilePath && $csvHeaders)
                <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-12 flex items-center justify-center min-h-[400px]">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 w-full max-w-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $uploadedFile?->getClientOriginalName() ?? 'import.csv' }}</p>
                                <p class="text-xs text-gray-500">{{ Number::fileSize($uploadedFile?->getSize() ?? 0, precision: 1) }}</p>
                            </div>
                            <button type="button" wire:click="removeFile" class="p-1.5 rounded-lg text-gray-400 hover:text-danger-500">
                                <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="border-t border-dashed border-gray-200 dark:border-gray-700 my-4"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Columns</p>
                                <p class="text-xl font-semibold text-gray-950 dark:text-white">{{ count($csvHeaders) }}</p>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Rows</p>
                                <p class="text-xl font-semibold text-gray-950 dark:text-white">{{ number_format($rowCount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div x-data="{ dragging: false }" x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false" x-on:drop.prevent="dragging = false"
                    :class="dragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-900'"
                    class="relative rounded-xl border border-dashed p-12 text-center min-h-[400px] flex flex-col items-center justify-center">
                    <input type="file" wire:model="uploadedFile" accept=".csv,.xlsx,.xls,.ods,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-12 w-12 text-gray-400" />
                    <p class="mt-6 text-sm text-gray-700 dark:text-gray-400">Drop your .CSV or .XLSX file here</p>
                    <x-filament::button color="gray" class="mt-6 pointer-events-none">Choose a file</x-filament::button>
                    @error('uploadedFile')<p class="mt-4 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
            @endif
            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button wire:click="nextStep" :disabled="!$this->canProceedToNextStep()" icon="heroicon-m-arrow-right" icon-position="after">Continue</x-filament::button>
            </div>
        </div>
    @endif

    {{-- Step 2: Map Columns --}}
    @if ($currentStep === 2)
        <div class="space-y-6" x-data="{ hovered: '{{ $csvHeaders[0] ?? '' }}' }" wire:ignore.self>
            <div class="flex gap-6">
                <div class="flex-1">
                    <div class="flex items-center pb-2 mb-1 text-xs font-medium text-gray-500 uppercase">
                        <div class="flex-1">File column</div><div class="w-6"></div><div class="flex-1">Attribute</div>
                    </div>
                    @foreach ($csvHeaders as $header)
                        @php $mapped = array_search($header, $columnMap); @endphp
                        <div wire:key="map-{{ md5($header) }}" class="flex items-center py-2 px-2 -mx-2 rounded-lg" :class="hovered === '{{ addslashes($header) }}' ? 'bg-primary-50 dark:bg-primary-950/30' : ''" @mouseenter="hovered = '{{ addslashes($header) }}'">
                            <div class="flex-1 text-sm text-gray-950 dark:text-white">{{ $header }}</div>
                            <div class="w-6 flex justify-center"><x-filament::icon icon="heroicon-m-arrow-right" class="h-3.5 w-3.5 text-gray-300" /></div>
                            <div class="flex-1">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:change="mapCsvColumnToField('{{ addslashes($header) }}', $event.target.value)">
                                        <option value="" @selected($mapped === false)>Select attribute</option>
                                        @foreach ($this->importerColumns as $col)
                                            <option value="{{ $col->getName() }}" @selected($mapped === $col->getName()) @disabled(!empty($columnMap[$col->getName()]) && $columnMap[$col->getName()] !== $header)>
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
                        @foreach ($csvHeaders as $header)
                            <div x-show="hovered === '{{ addslashes($header) }}'" x-cloak>
                                @foreach ($this->getColumnPreviewValues($header, 5) as $v)
                                    <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-300">{{ $v ?: '(blank)' }}</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @php $unmapped = collect($this->importerColumns)->filter(fn($c) => $c->isMappingRequired() && empty($columnMap[$c->getName()]))->pluck('label'); @endphp
            @if ($unmapped->isNotEmpty())
                <div class="flex items-start gap-2 p-3 rounded-lg bg-warning-50 dark:bg-warning-950/50 border border-warning-200 dark:border-warning-800">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-warning-500" />
                    <span class="text-sm"><strong>Required:</strong> {{ $unmapped->join(', ') }}</span>
                </div>
            @endif
            <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button wire:click="resetWizard" color="gray">Start over</x-filament::button>
                <x-filament::button wire:click="nextStep" :disabled="!$this->canProceedToNextStep()">Continue</x-filament::button>
            </div>
        </div>
    @endif

    {{-- Step 3: Review Values --}}
    @if ($currentStep === 3)
        @php
            $analysis = $expandedColumn ? $this->columnAnalyses->firstWhere('mappedToField', $expandedColumn) : $this->columnAnalyses->first();
            $values = $analysis?->paginatedValues($reviewPage, 100) ?? [];
            $total = $analysis?->uniqueCount ?? 0;
            $showing = min($reviewPage * 100, $total);
        @endphp
        <div class="space-y-6">
            <div class="flex gap-6 min-h-[500px]">
                <div class="w-56 shrink-0">
                    <div class="text-xs font-medium text-gray-500 uppercase px-1 mb-2">Columns</div>
                    @foreach ($this->columnAnalyses as $a)
                        <button type="button" wire:click="toggleColumn('{{ $a->mappedToField }}')" wire:key="col-{{ $a->mappedToField }}"
                            @class(['w-full text-left px-2.5 py-2 rounded-lg', 'bg-primary-50 dark:bg-primary-950' => $expandedColumn === $a->mappedToField, 'hover:bg-gray-50 dark:hover:bg-gray-800' => $expandedColumn !== $a->mappedToField])>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm truncate">{{ $a->csvColumnName }}</span>
                                @if ($a->getErrorCount() > 0)<span class="text-xs px-1.5 py-0.5 rounded bg-danger-100 text-danger-700">{{ $a->getErrorCount() }}</span>@endif
                            </div>
                            <div class="text-xs text-gray-500">{{ $a->mappedToField }}</div>
                        </button>
                    @endforeach
                </div>
                <div class="flex-1 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden flex flex-col">
                    @if ($analysis)
                        <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-xs text-gray-500">
                            <span><strong>{{ number_format($total) }}</strong> unique</span>
                            <span>{{ number_format($showing) }} of {{ number_format($total) }}</span>
                        </div>
                        <div class="flex items-center px-3 py-2 border-b text-xs text-gray-500 uppercase">
                            <div class="flex-1">Raw</div><div class="w-8"></div><div class="flex-1">Mapped</div><div class="w-10"></div>
                        </div>
                        <div x-data="{ loading: false }" x-on:scroll.debounce.100ms="if (!loading && $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100) { loading = true; $wire.loadMoreValues().then(() => loading = false); }" class="overflow-y-auto flex-1 max-h-[400px]">
                            @forelse ($values as $val => $cnt)
                                @php
                                    $skip = $this->isValueSkipped($analysis->mappedToField, $val);
                                    $corr = $this->hasCorrectionForValue($analysis->mappedToField, $val);
                                    $disp = $val ?: '(blank)';
                                    $mapd = $corr ? $this->getCorrectedValue($analysis->mappedToField, $val) : $disp;
                                    $issue = $analysis->getIssueForValue($val);
                                    $err = $issue?->severity === 'error' && !$skip;
                                @endphp
                                <div wire:key="val-{{ md5($analysis->mappedToField.$val) }}" class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <div class="flex items-center">
                                        <div class="flex-1 flex items-center gap-2 min-w-0">
                                            <span @class(['text-sm truncate', 'text-gray-400 line-through' => $skip])>{{ $disp }}</span>
                                            <span class="text-xs text-gray-400">{{ $cnt }}x</span>
                                        </div>
                                        <div class="w-8 flex justify-center"><x-filament::icon icon="heroicon-m-arrow-right" class="h-3.5 w-3.5 text-gray-300" /></div>
                                        <div class="flex-1 min-w-0">
                                            @if ($skip)<span class="text-sm text-gray-400 italic">Skipped</span>
                                            @else<input type="text" value="{{ $mapd }}" x-on:blur="if($event.target.value !== '{{ addslashes($mapd) }}') $wire.correctValue('{{ $analysis->mappedToField }}', '{{ addslashes($val) }}', $event.target.value)" x-on:keydown.enter="$event.target.blur()" @class(['w-full px-2 py-1 text-sm rounded border focus:ring-1 focus:ring-primary-500', 'border-success-300 bg-success-50 dark:border-success-700 dark:bg-success-950' => $corr && !$err, 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' => !$corr || $err]) />
                                            @endif
                                        </div>
                                        <button type="button" wire:click="skipValue('{{ $analysis->mappedToField }}', '{{ addslashes($val) }}')" class="w-10 flex justify-end p-1 rounded {{ $skip ? 'text-primary-600 bg-primary-100' : 'text-gray-400 hover:text-gray-600' }}">
                                            <x-filament::icon icon="heroicon-o-no-symbol" class="h-4 w-4" />
                                        </button>
                                    </div>
                                    @if ($err)<div class="mt-1 text-xs text-danger-600"><x-filament::icon icon="heroicon-m-exclamation-circle" class="h-3.5 w-3.5 inline -mt-0.5" /> {{ $issue->message }}</div>@endif
                                </div>
                            @empty
                                <div class="px-3 py-8 text-center text-sm text-gray-500">No values</div>
                            @endforelse
                            @if ($showing < $total)
                                <div class="px-3 py-3 text-center text-sm text-gray-400" wire:key="more-{{ $reviewPage }}">
                                    <span x-show="!loading">Scroll for more...</span>
                                    <span x-show="loading" x-cloak><x-filament::loading-indicator class="h-4 w-4 inline" /> Loading...</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex-1 flex items-center justify-center text-sm text-gray-500">Select a column</div>
                    @endif
                </div>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button wire:click="previousStep" color="gray">Back</x-filament::button>
                <div class="flex items-center gap-4">
                    @if ($this->hasValidationErrors())
                        <span class="text-sm text-danger-600"><x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5 inline" /> {{ $this->getTotalErrorCount() }} errors</span>
                    @endif
                    <x-filament::button wire:click="nextStep">Continue</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Step 4: Preview --}}
    @if ($currentStep === 4)
        @php
            $total = $this->previewResultData['totalRows'] ?? 0;
            $rows = $previewRows;
            $showCo = in_array($entityType, ['people', 'opportunities']);
            $idUp = collect($rows)->where('_update_method', 'id')->count();
            $attrUp = collect($rows)->where('_update_method', 'attribute')->count();
        @endphp
        <div class="space-y-6">
            <div class="flex items-center gap-6 text-sm">
                <span><x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4 inline text-gray-400" /> <strong>{{ number_format($total) }}</strong> rows</span>
                <span><x-filament::icon icon="heroicon-o-plus-circle" class="h-4 w-4 inline text-success-500" /> <strong class="text-success-600">~{{ number_format($this->getCreateCount()) }}</strong> new</span>
                <span><x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 inline text-info-500" /> <strong class="text-info-600">~{{ number_format($this->getUpdateCount()) }}</strong> updates</span>
            </div>
            @if ($idUp || $attrUp)
                <div class="flex items-center gap-4 text-sm pt-2 border-t border-gray-100">
                    <span class="text-xs font-medium text-gray-500 uppercase">Update method:</span>
                    @if ($idUp)<x-filament::badge color="info" size="sm">{{ $idUp }}</x-filament::badge> <span class="text-gray-500">by ID</span>@endif
                    @if ($attrUp)<x-filament::badge color="warning" size="sm">{{ $attrUp }}</x-filament::badge> <span class="text-gray-500">by attribute</span>@endif
                </div>
            @endif
            @if (count($rows))
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/50 border-b flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500 uppercase">Sample</span>
                        @if ($total > count($rows))<span class="text-xs text-gray-400">{{ count($rows) }} of {{ number_format($total) }}</span>@endif
                    </div>
                    <div class="overflow-x-auto max-h-80">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800/50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">#</th>
                                    @foreach (array_keys($columnMap) as $f)
                                        @if ($columnMap[$f] !== '')<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">{{ str($f)->headline() }}</th>@endif
                                    @endforeach
                                    @if ($showCo)<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Company</th>@endif
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($rows as $i => $row)
                                    <tr wire:key="row-{{ $i }}">
                                        <td class="px-3 py-2 text-gray-500">{{ $row['_row_index'] ?? $i + 1 }}</td>
                                        @foreach (array_keys($columnMap) as $f)
                                            @if ($columnMap[$f] !== '')<td class="px-3 py-2 max-w-xs truncate">{{ $row[$f] ?? '-' }}</td>@endif
                                        @endforeach
                                        @if ($showCo)
                                            <td class="px-3 py-2">
                                                <span class="truncate max-w-[100px]">{{ $row['_company_name'] ?? $row['company_name'] ?? '-' }}</span>
                                                <x-filament::badge size="sm" :color="match($row['_company_match_type'] ?? 'new') { 'domain' => 'success', 'name' => 'info', 'ambiguous' => 'warning', default => 'gray' }">
                                                    {{ ucfirst($row['_company_match_type'] ?? 'new') }}
                                                </x-filament::badge>
                                            </td>
                                        @endif
                                        <td class="px-3 py-2">
                                            <x-filament::badge size="sm" :color="($row['_is_new'] ?? true) ? 'success' : (($row['_update_method'] ?? '') === 'id' ? 'info' : 'warning')" :icon="($row['_is_new'] ?? true) ? 'heroicon-m-plus' : 'heroicon-m-arrow-path'">
                                                {{ ($row['_is_new'] ?? true) ? 'New' : (($row['_update_method'] ?? '') === 'id' ? 'Update by ID' : 'Update') }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button wire:click="previousStep" color="gray">Back</x-filament::button>
                <x-filament::button wire:click="executeImport" :disabled="!$this->hasRecordsToImport()">Start Import</x-filament::button>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</div>
