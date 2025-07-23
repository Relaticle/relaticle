<x-filament-panels::page>
    @livewire(Laravel\Jetstream\Http\Livewire\UpdateTeamNameForm::class, ['team' =>$this->tenant])

    @livewire(Laravel\Jetstream\Http\Livewire\TeamMemberManager::class, ['team' =>$this->tenant])

    @if (Gate::check('delete', $this->tenant) && ! $this->tenant->personal_team)
        <x-section-border/>

        @livewire(Laravel\Jetstream\Http\Livewire\DeleteTeamForm::class, ['team' =>$this->tenant])
    @endif
</x-filament-panels::page>
