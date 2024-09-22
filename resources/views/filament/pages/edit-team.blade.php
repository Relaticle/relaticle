<x-filament-panels::page>
    @livewire(Laravel\Jetstream\Http\Livewire\UpdateTeamNameForm::class, compact('team'))

    @livewire(Laravel\Jetstream\Http\Livewire\TeamMemberManager::class, compact('team'))

    @if (Gate::check('delete', $team) && ! $team->personal_team)
        <x-section-border/>
        
        @livewire(Laravel\Jetstream\Http\Livewire\DeleteTeamForm::class, compact('team'))
    @endif
</x-filament-panels::page>
