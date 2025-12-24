{{-- Step 4: Preview --}}
@php
    $total = $this->previewResultData['totalRows'] ?? 0;
    $rows = $this->previewRows;
    $showCo = in_array($this->entityType, ['people', 'opportunities']);
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
            <div class="overflow-x-auto max-h-64">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">#</th>
                            @foreach (array_keys($this->columnMap) as $f)
                                @if ($this->columnMap[$f] !== '')<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">{{ str($f)->headline() }}</th>@endif
                            @endforeach
                            @if ($showCo)<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Company</th>@endif
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($rows as $i => $row)
                            <tr wire:key="row-{{ $i }}">
                                <td class="px-3 py-2 text-gray-500">{{ $row['_row_index'] ?? $i + 1 }}</td>
                                @foreach (array_keys($this->columnMap) as $f)
                                    @if ($this->columnMap[$f] !== '')<td class="px-3 py-2 max-w-xs truncate">{{ $row[$f] ?? '-' }}</td>@endif
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
    @else
        <div class="p-8 text-center text-sm text-gray-500 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
            No preview data available
        </div>
    @endif
</div>
