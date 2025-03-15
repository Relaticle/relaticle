<?php

declare(strict_types=1);

namespace App\Livewire\TasksBoard;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\View\View;
use Livewire\Component;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class RecordComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Task $task;

    public function render(): View
    {
        return view('filament.pages.tasks-board.record-component');
    }

    private function editAction(): Action
    {
        return Action::make('Edit')
            ->iconButton()
            ->icon('heroicon-o-plus-circle')
            ->model(Task::class)
            ->record($this->task)
            ->form([
                Forms\Components\TextInput::make('title')->rules(['max:255'])->required(),
                CustomFieldsComponent::make(),
            ])
            ->fillForm($this->task->attributesToArray())
            ->action(fn (array $data) => $this->task->update($data));
    }
}
