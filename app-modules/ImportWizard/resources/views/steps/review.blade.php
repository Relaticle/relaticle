{{-- Step 3: Review Values --}}
@php
    $analysis = $this->expandedColumn ? $this->columnAnalyses->firstWhere('mappedToField', $this->expandedColumn) : $this->columnAnalyses->first();
    $values = $analysis?->paginatedValues($this->reviewPage, 100) ?? [];
    $total = $analysis?->uniqueCount ?? 0;
    $showing = min($this->reviewPage * 100, $total);
@endphp
<div class="space-y-6">
    <div class="flex gap-6 min-h-[400px]">
        <div class="w-56 shrink-0">
            <div class="text-xs font-medium text-gray-500 uppercase px-1 mb-2">Columns</div>
            @foreach ($this->columnAnalyses as $a)
                <button type="button" wire:click="toggleColumn('{{ $a->mappedToField }}')" wire:key="col-{{ $a->mappedToField }}"
                    @class(['w-full text-left px-2.5 py-2 rounded-lg', 'bg-primary-50 dark:bg-primary-950' => $this->expandedColumn === $a->mappedToField, 'hover:bg-gray-50 dark:hover:bg-gray-800' => $this->expandedColumn !== $a->mappedToField])>
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
                <div x-data="{ loading: false }" x-on:scroll.debounce.100ms="if (!loading && $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100) { loading = true; $wire.loadMoreValues().then(() => loading = false); }" class="overflow-y-auto flex-1 max-h-[300px]">
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
                        <div class="px-3 py-3 text-center text-sm text-gray-400" wire:key="more-{{ $this->reviewPage }}">
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
    @if ($this->hasValidationErrors())
        <div class="flex items-center gap-2 p-3 rounded-lg bg-danger-50 dark:bg-danger-950/50 border border-danger-200 dark:border-danger-800">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5 text-danger-500" />
            <span class="text-sm text-danger-700 dark:text-danger-300">{{ $this->getTotalErrorCount() }} validation errors to fix or skip</span>
        </div>
    @endif
</div>
