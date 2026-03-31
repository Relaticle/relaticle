<div class="space-y-3 p-4">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
        {{ $fieldLabel }} History
    </h3>

    @if(empty($history))
        <p class="py-4 text-center text-xs text-gray-400 dark:text-gray-500">
            No changes recorded for this field
        </p>
    @else
        <div class="space-y-0">
            @foreach($history as $change)
                <div @class([
                    'flex items-start gap-3 py-2.5',
                    'border-t border-gray-100 dark:border-white/5' => ! $loop->first,
                ])>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $change['causer_name'] }}
                            </span>
                            <time
                                class="shrink-0 text-xs text-gray-400 dark:text-gray-500"
                                title="{{ $change['created_at'] }}"
                            >
                                {{ $change['created_at_human'] }}
                            </time>
                        </div>
                        <div class="mt-0.5 flex items-center gap-1.5 text-xs">
                            <span class="text-gray-400 line-through dark:text-gray-500">{{ $change['old_value'] }}</span>
                            <span class="text-gray-300 dark:text-gray-600">&rarr;</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $change['new_value'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
