<?php

namespace App\Livewire\TasksBoard;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class StatusComponent extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $status;

    public function render()
    {
        return view('filament.pages.tasks-board.status-component');
    }
}
