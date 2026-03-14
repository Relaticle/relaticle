@props([
    'name' => 'oidc',
])

@if (Str::startsWith($name, ['http://', 'https://']))
    <img
        src="{{ $name }}"
        {{ $attributes->merge(['class' => 'w-5 h-5']) }}
    />
@else
    @php
        $component = "icons.$name";
    @endphp

    @if (View::exists("components.$component"))
        <x-dynamic-component
            :component="$component"
            {{ $attributes->merge(['class' => 'w-5 h-5']) }}
        />
    @else
        <x-icons.oidc
            {{ $attributes->merge(['class' => 'w-5 h-5']) }}
        />
    @endif
@endif
