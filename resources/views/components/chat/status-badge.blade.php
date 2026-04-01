@props([
    'status' => 'pending',
    'label' => null,
])

@php
    $colors = match($status) {
        'approved', 'created', 'updated' => 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
        'expired' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
        'deleted' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
        default => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400',
    };
    $displayLabel = $label ?? ucfirst($status);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {$colors}"]) }}>
    {{ $displayLabel }}
</span>
