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

final class HeaderComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $status;

    private function createAction(): Action
    {
        return Action::make('Create')
            ->iconButton()
            ->icon('heroicon-o-plus-circle')
            ->model(Task::class)
            ->form([
                Forms\Components\TextInput::make('title')->required(),
                CustomFieldsComponent::make(),
            ])
            ->fillForm([
                'custom_fields.status' => $this->status['id'], // TODO: Implement this functionality, currently not working
            ]);
    }

    public function render(): View
    {
        return view('filament.pages.tasks-board.header-component');
    }
}
