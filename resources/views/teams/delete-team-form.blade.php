<x-action-section>
    <x-slot name="title">
        {{ __('Delete Team') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Permanently delete this team.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl prose dark:prose-invert text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once a team is deleted, all of its resources and data will be permanently deleted. Before deleting this team, please download any data or information regarding this team that you wish to retain.') }}
        </div>

        <!-- Delete Team Confirmation Modal -->
        <x-filament::modal id="delete-team-modal"
                           icon="heroicon-o-exclamation-triangle"
                           icon-color="danger"
                           width="md"
                           alignment="center"
                           footerActionsAlignment="center"
        >
            <x-slot name="trigger">
                <x-filament::button color="danger"
                                    class="mt-5"
                                    wire:loading.attr="disabled">
                    {{ __('Delete Team') }}
                </x-filament::button>
            </x-slot>

            <x-slot name="heading">
                {{ __('Delete Team') }}
            </x-slot>

            <x-slot name="description">
                {{ __('Are you sure you want to delete this team? Once a team is deleted, all of its resources and data will be permanently deleted.') }}
            </x-slot>

            <x-slot name="footerActions">
                <x-filament::button color="gray"
                                    block
                                    x-on:click="$dispatch('close-modal', { id: 'delete-team-modal' })"
                                    wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-filament::button>

                <x-filament::button color="danger"
                                    wire:click="deleteTeam"
                                    wire:loading.attr="disabled">
                    {{ __('Delete Team') }}
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    </x-slot>
</x-action-section>
