<div>
    <h3 class="mb-2 px-4 font-semibold text-lg flex justify-between">
        {{ $status['name'] }}

        {{ $this->createAction() }}
    </h3>

    <x-filament-actions::modals/>
</div>
