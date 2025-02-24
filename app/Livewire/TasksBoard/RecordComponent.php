<?php

namespace App\Livewire\TasksBoard;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Filament\Forms;

class RecordComponent extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Task $task;

    public function render()
    {
        return view('filament.pages.tasks-board.record-component');
    }

    protected function editAction(): Action
    {
        return Action::make('Edit')
            ->iconButton()
            ->icon('heroicon-o-plus-circle')
            ->model(Task::class)
            ->record($this->task)
            ->form([
                Forms\Components\TextInput::make('title')->required(),
                CustomFieldsComponent::make()
            ])
            ->fillForm($this->task->attributesToArray())
            ->action(fn(array $data) => $this->task->update($data));
    }
}
