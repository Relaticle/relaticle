<div class="space-y-4">
    {{-- Filter Tabs --}}
    <div class="flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
        @foreach (['all', 'created', 'updated', 'deleted'] as $f)
            <button
                type="button"
                wire:click="setFilter('{{ $f }}')"
                @class([
                    'rounded-md px-3 py-1.5 text-sm text-gray-700 transition dark:text-gray-300',
                    'bg-white shadow-sm dark:bg-white/10 font-medium' => $filter === $f,
                    'hover:bg-white/50 dark:hover:bg-white/5' => $filter !== $f,
                ])
            >
                {{ ucfirst($f) }}
            </button>
        @endforeach
    </div>

    {{-- Timeline --}}
    <div class="max-h-[500px] space-y-0 overflow-y-auto" style="scrollbar-gutter: stable;">
        @if (empty($data['entries']))
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <x-filament::icon
                    icon="heroicon-o-clock"
                    class="mb-3 h-12 w-12 text-gray-400 dark:text-gray-500"
                />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No activity yet</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Changes to this record will appear here</p>
            </div>
        @else
            @foreach ($data['entries'] as $entry)
                <div class="relative flex gap-3 py-3" wire:key="activity-{{ $entry['id'] }}">
                    {{-- Timeline Line --}}
                    @if (! $loop->last)
                        <div class="absolute -bottom-0 left-4 top-8 w-px bg-gray-200 dark:bg-white/10"></div>
                    @endif

                    {{-- Icon --}}
                    <div @class([
                        'relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                        'bg-green-50 dark:bg-green-950/30' => $entry['event'] === 'created',
                        'bg-blue-50 dark:bg-blue-950/30' => $entry['event'] === 'updated',
                        'bg-red-50 dark:bg-red-950/30' => $entry['event'] === 'deleted',
                        'bg-amber-50 dark:bg-amber-950/30' => $entry['event'] === 'restored',
                    ])>
                        @switch($entry['event'])
                            @case('created')
                                <x-filament::icon icon="heroicon-o-plus-circle" class="h-4 w-4 text-green-500 dark:text-green-400" />
                                @break
                            @case('updated')
                                <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4 text-blue-500 dark:text-blue-400" />
                                @break
                            @case('deleted')
                                <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4 text-red-500 dark:text-red-400" />
                                @break
                            @case('restored')
                                <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="h-4 w-4 text-amber-500 dark:text-amber-400" />
                                @break
                        @endswitch
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1 pt-0.5">
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                @if ($entry['causer_avatar'])
                                    <img
                                        src="{{ $entry['causer_avatar'] }}"
                                        alt="{{ $entry['causer_name'] }}"
                                        class="h-5 w-5 shrink-0 rounded-full"
                                    />
                                @endif
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $entry['description'] }}</p>
                            </div>
                            <time
                                class="shrink-0 text-xs text-gray-400 dark:text-gray-500"
                                title="{{ $entry['created_at'] }}"
                            >
                                {{ $entry['created_at_human'] }}
                            </time>
                        </div>

                        {{-- Attribute Changes --}}
                        @if (! empty($entry['changes']['old']))
                            <div class="mt-1.5 rounded-md border border-gray-100 bg-gray-50/50 p-2 dark:border-white/5 dark:bg-white/5">
                                @foreach ($entry['changes']['attributes'] as $field => $newVal)
                                    <div class="flex items-center gap-1.5 py-0.5 text-xs">
                                        <span class="font-medium text-gray-500 dark:text-gray-400">{{ str($field)->when(str($field)->endsWith('_id'), fn ($s) => $s->beforeLast('_id'))->headline() }}</span>
                                        <span class="text-gray-400 line-through dark:text-gray-500">{{ $entry['changes']['old'][$field] ?? '(empty)' }}</span>
                                        <span class="text-gray-400 dark:text-gray-500">&rarr;</span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $newVal }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Load More --}}
            @if ($data['hasMore'])
                <div class="flex justify-center pt-3">
                    <button
                        type="button"
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-primary-600 transition hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-950/30"
                    >
                        <span wire:loading.remove wire:target="loadMore">Load more</span>
                        <span wire:loading wire:target="loadMore">
                            <x-filament::loading-indicator class="h-4 w-4" />
                        </span>
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
