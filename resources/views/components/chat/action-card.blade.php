@props([
    'action' => null,
])

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="flex items-center gap-2">
        <span @class([
            'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium',
            'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' => ($action['operation'] ?? '') === 'create',
            'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' => ($action['operation'] ?? '') === 'update',
            'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' => ($action['operation'] ?? '') === 'delete',
        ])>
            {{ ucfirst($action['operation'] ?? 'Action') }}
        </span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ $action['display']['summary'] ?? '' }}
        </span>
    </div>

    @if(!empty($action['display']['fields']))
        <div class="mt-2 space-y-1">
            @foreach($action['display']['fields'] as $field)
                <div class="flex gap-2 text-sm">
                    <span class="font-medium text-gray-500 dark:text-gray-400">{{ $field['label'] }}:</span>
                    <span class="text-gray-900 dark:text-white">{{ $field['new'] ?? $field['value'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{ $slot }}
</div>
