<x-filament::section aside>
    <x-slot name="heading">
        Manage Access Tokens
    </x-slot>
    <x-slot name="description">
        You may delete any of your existing tokens if they are no longer needed.
    </x-slot>

    {{ $this->table }}

    <x-filament-actions::modals />
</x-filament::section>
