@php
    $state = $getState();
    $hasAvatar = !empty($state['avatar']);

    // Get the label from the component
    $label = $getLabel();

    // Map text size to Tailwind class
    $textSizeClass = match ($state['textSize'] ?? 'sm') {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'base' => 'text-base',
        'lg' => 'text-lg',
        'xl' => 'text-xl',
        default => 'text-sm',
    };
@endphp

<div>
    @if ($label)
        <div class="flex items-center justify-between gap-2 mb-1">
            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                {{ $label }}
            </span>
        </div>
    @endif

    <div class="flex items-center space-x-2">
        @if ($hasAvatar)
            <x-filament::avatar
                src="{{ $state['avatar'] }}"
                alt="{{ $state['name'] ?? '' }}"
                size="{{ $state['avatarSize'] }}"
                :circular="$state['circular']"
            />
        @endif

        @if ($state['name'])
            <span class="{{ $textSizeClass }} font-medium">{{ $state['name'] }}</span>
        @endif
    </div>
</div>
