<x-filament-panels::form wire:submit.prevent="editModalFormSubmitted">
    <x-filament::modal id="kanban--edit-record-modal" :slideOver="$this->getEditModalSlideOver()" :width="$this->getEditModalWidth()">
        <x-slot name="header">
            <x-filament::modal.heading>
                {{ $this->getEditModalTitle() }}
            </x-filament::modal.heading>
        </x-slot>

        {{ $this->form }}

        <x-slot name="footer">
            <x-filament::button type="submit">
                {{$this->getEditModalSaveButtonLabel()}}
            </x-filament::button>

            <x-filament::button color="gray" x-on:click="isOpen = false">
                {{$this->getEditModalCancelButtonLabel()}}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::form>
