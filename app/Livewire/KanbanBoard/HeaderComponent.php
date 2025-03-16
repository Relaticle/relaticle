<?php

declare(strict_types=1);

namespace App\Livewire\KanbanBoard;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

final class HeaderComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $status;
    public string $modelClass;
    public ?string $boardClass = null;

    public function mount(array $status, string $modelClass, ?string $boardClass = null): void
    {
        $this->status = $status;
        $this->modelClass = $modelClass;
        $this->boardClass = $boardClass;
    }

    public function createAction(): Action
    {
        $board = app($this->boardClass);

        return Action::make('Create')
            ->iconButton()
            ->icon('heroicon-o-plus-circle')
            ->model($this->modelClass)
            ->slideOver()
            ->form($board->getFormSchema())
            ->fillForm($board->getDefaultFormData($this->status))
            ->action(function (array $data) use($board) {
                $record = $board->createRecord($data);

                $this->dispatch(
                    'record-created',
                    recordId: $record->id,
                    statusId: $this->status['id']
                );

                return $record;
            });
    }

    public function render(): View
    {
        return view('filament.pages.kanban-board.header-component');
    }
}
