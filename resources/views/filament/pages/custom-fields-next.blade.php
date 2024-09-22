<x-filament-panels::page>
    <x-filament::section>
        Companies
    </x-filament::section>

    <x-filament::tabs label="Content tabs" contained>
        <x-filament::tabs.item active>
            Tab 1
        </x-filament::tabs.item>

        <x-filament::tabs.item>
            Tab 2
        </x-filament::tabs.item>

        <x-filament::tabs.item>
            Tab 3
        </x-filament::tabs.item>
    </x-filament::tabs>
    <div
        x-data="{}"
        x-sortable
        x-on:end="console.log('Sorting ended!', $event.detail)"
        class="flex flex-col gap-y-6"
    >
        @foreach ($this->sections as $section)
            <x-filament::section x-sortable-item="{{ $section['id'] }}" compact>
                <x-slot name="heading">
                    <div class="flex justify-between">
                        <div class="flex items-center gap-x-1">
                            <x-filament::icon-button
                                icon="heroicon-m-bars-3"
                                color="gray"
                                x-sortable-handle
                            />
                            {{$section['name']}}
                        </div>

                        <div class="flex items-center gap-x-1">
                            <x-filament::icon-button
                                color="gray"
                                icon="heroicon-m-pencil"
                                wire:click="openNewUserModal"
                                label="New label"
                            />
                            <x-filament::icon-button
                                color="gray"
                                icon="heroicon-m-trash"
                                wire:click="openNewUserModal"
                                label="New label"
                            />
                        </div>
                    </div>
                </x-slot>

                <div
                    x-data="{}"
                    x-sortable
                    x-sortable-group="fields"
                    x-on:end="console.log('Sorting ended!', $event.detail)"
                    class="flex flex-col gap-2"
                >
                    @foreach ($section['fields'] as $field)

                        <div
                            x-sortable-item="{{ $field }}"
                            class="bg-gray-50  py-0.5 rounded border border-gray-200 text-gray-900">
                            <div class="flex justify-between">
                                <div class="flex items-center gap-x-2">
                                    <x-filament::icon-button
                                        icon="heroicon-m-bars-3"
                                        color="gray"
                                        x-sortable-handle
                                    />
                                        <x-filament::link :href="'#'">
                                            {{ $field }}
                                        </x-filament::link>
                                </div>

                                <div class="flex items-center gap-x-1 px-2">
                                    <x-filament::icon-button
                                        color="gray"
                                        size="xs"
                                        icon="heroicon-m-trash"
                                        wire:click="openNewUserModal"
                                        label="New label"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-slot name="footerActions">
                    {{ $this->createFieldAction }}
                </x-slot>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
