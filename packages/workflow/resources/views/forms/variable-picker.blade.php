<div class="space-y-4">
    @forelse($groups as $group)
        <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                {{ $group['label'] }}
            </h4>
            <div class="space-y-0.5">
                @foreach($group['fields'] as $field)
                    <button
                        type="button"
                        x-on:click="
                            $dispatch('variable-selected', { path: '{{ $field['path'] }}' });
                            $wire.mountedActions = [];
                        "
                        class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <code class="text-xs font-mono text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-1.5 py-0.5 rounded">
                                @{{ '{{' . $field['path'] . '}}' }}
                            </code>
                            <span class="text-gray-600 dark:text-gray-300 truncate">{{ $field['label'] }}</span>
                        </div>
                        <span class="text-gray-400 dark:text-gray-500 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                            {{ $field['type'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-6 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No variables available.</p>
            <p class="text-xs mt-1">Add upstream blocks to make variables available here.</p>
        </div>
    @endforelse
</div>
