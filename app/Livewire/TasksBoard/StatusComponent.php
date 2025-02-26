<?php

declare(strict_types=1);

namespace App\Livewire\TasksBoard;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\View\View;
use Livewire\Component;

final class StatusComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $status;

    public function render(): View
    {
        return view('filament.pages.tasks-board.status-component');
    }
}
