<div class="space-y-4" wire:poll.30s>
    {{-- Filter Tabs --}}
    <div class="flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
        @foreach (['all', 'created', 'updated', 'deleted', 'restored'] as $f)
            <button
                type="button"
                wire:click="setFilter('{{ $f }}')"
                @class([
                    'rounded-md px-3 py-1.5 text-xs font-medium transition',
                    'bg-white text-gray-900 shadow-sm dark:bg-white/10 dark:text-white' => $filter === $f,
                    'text-gray-500 hover:text-gray-700 hover:bg-white/50 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-white/5' => $filter !== $f,
                ])
            >
                {{ ucfirst($f) }}
            </button>
        @endforeach
    </div>

    {{-- Timeline --}}
    <div class="max-h-[600px] space-y-0 overflow-y-auto" style="scrollbar-gutter: stable;">
        @if (empty($data['groups']))
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <x-filament::icon
                    icon="heroicon-o-clock"
                    class="mb-3 h-10 w-10 text-gray-300 dark:text-gray-600"
                />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No activity yet</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Changes to this record will appear here</p>
            </div>
        @else
            @foreach ($data['groups'] as $group)
                {{-- Date Group Header --}}
                <div class="sticky top-0 z-20 -mx-1 px-1 py-2 backdrop-blur-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ $group['label'] }}
                    </span>
                </div>

                @foreach ($group['entries'] as $entry)
                    <div
                        class="group relative flex gap-3 rounded-lg px-1 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                        wire:key="activity-{{ $entry['id'] }}"
                    >
                        {{-- Timeline Line --}}
                        @if (! $loop->last)
                            <div class="absolute bottom-0 left-[19px] top-10 w-px bg-gray-100 dark:bg-white/5"></div>
                        @endif

                        {{-- Avatar / Initials / Icon --}}
                        <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center">
                            @if ($entry['causer_avatar'])
                                <img
                                    src="{{ $entry['causer_avatar'] }}"
                                    alt="{{ $entry['causer_name'] }}"
                                    class="h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-900"
                                />
                            @elseif ($entry['causer_initials'])
                                <div @class([
                                    'flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium ring-2 ring-white dark:ring-gray-900',
                                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $entry['event'] === 'created',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' => $entry['event'] === 'updated',
                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $entry['event'] === 'deleted',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $entry['event'] === 'restored',
                                ])>
                                    {{ $entry['causer_initials'] }}
                                </div>
                            @else
                                <div @class([
                                    'flex h-8 w-8 items-center justify-center rounded-full',
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
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 flex-1 pt-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">{{ $entry['causer_name'] ?? 'System' }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        @if ($entry['event'] === 'created')
                                            created this record
                                        @elseif ($entry['event'] === 'deleted')
                                            deleted this record
                                        @elseif ($entry['event'] === 'restored')
                                            restored this record
                                        @elseif ($entry['field_count'] > 0)
                                            updated {{ $entry['field_count'] }} {{ str('field')->plural($entry['field_count']) }}
                                        @else
                                            updated this record
                                        @endif
                                    </span>
                                </p>
                                <time
                                    class="shrink-0 text-xs text-gray-400 dark:text-gray-500"
                                    title="{{ $entry['created_at'] }}"
                                >
                                    {{ $entry['created_at_time'] }}
                                </time>
                            </div>

                            {{-- Attribute Changes --}}
                            @php
                                $oldAttributes = (array) data_get($entry, 'changes.old', []);
                                $newAttributes = (array) data_get($entry, 'changes.attributes', []);
                            @endphp

                            @if (! empty($oldAttributes))
                                <div
                                    x-data="{ expanded: {{ count($newAttributes) <= 3 ? 'true' : 'false' }} }"
                                    class="mt-1.5"
                                >
                                    @if (count($newAttributes) > 3)
                                        <button
                                            type="button"
                                            x-on:click="expanded = !expanded"
                                            class="mb-1 flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                        >
                                            <x-filament::icon
                                                icon="heroicon-o-chevron-right"
                                                class="h-3 w-3 transition-transform"
                                                x-bind:class="expanded && 'rotate-90'"
                                            />
                                            <span x-text="expanded ? 'Hide changes' : 'Show {{ count($newAttributes) }} changes'"></span>
                                        </button>
                                    @endif

                                    <div
                                        x-show="expanded"
                                        x-collapse
                                        class="rounded-md border border-gray-100 bg-gray-50/50 dark:border-white/5 dark:bg-white/[0.02]"
                                    >
                                        @foreach ($newAttributes as $field => $newVal)
                                            <div @class([
                                                'flex items-baseline gap-1.5 px-2.5 py-1.5 text-xs',
                                                'border-t border-gray-100 dark:border-white/5' => ! $loop->first,
                                            ])>
                                                <span class="shrink-0 font-medium text-gray-500 dark:text-gray-400">
                                                    {{ str($field)->when(str($field)->endsWith('_id'), fn ($s) => $s->beforeLast('_id'))->headline() }}
                                                </span>
                                                <span class="truncate text-gray-400 line-through dark:text-gray-500">{{ $oldAttributes[$field] ?? '(empty)' }}</span>
                                                <span class="shrink-0 text-gray-300 dark:text-gray-600">&rarr;</span>
                                                <span class="truncate font-medium text-gray-700 dark:text-gray-300">{{ $newVal }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach

            {{-- Load More --}}
            @if ($data['hasMore'])
                <div class="flex justify-center pb-1 pt-3">
                    <button
                        type="button"
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        class="rounded-lg px-4 py-2 text-xs font-medium text-primary-600 transition hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-950/30"
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
