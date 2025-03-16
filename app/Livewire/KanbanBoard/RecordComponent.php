<?php

declare(strict_types=1);

namespace App\Livewire\KanbanBoard;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Component;

final class RecordComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Model $record;

    public ?string $boardClass = null;

    public function mount(Model $record, ?string $boardClass = null): void
    {
        $this->record = $record;
        $this->boardClass = $boardClass;
    }

    public function editAction(): Action
    {
        $modelClass = $this->record::class;

        return Action::make('Edit')
            ->iconButton()
            ->icon('heroicon-o-pencil')
            ->model($modelClass)
            ->record($this->record)
            ->form($this->getFormSchema())
            ->fillForm($this->record->attributesToArray())
            ->action(function (array $data): void {
                // If board class is provided, delegate record update to it
                if ($this->boardClass && class_exists($this->boardClass)) {
                    $board = app($this->boardClass);
                    $board->updateRecord($this->record, $data);
                } else {
                    // Fallback to direct update
                    $this->record->update($data);
                }
            });
    }

    /**
     * Get the form schema from the board class or use default
     */
    protected function getFormSchema(): array
    {
        // Get the model class from the task
        $modelClass = $this->record::class;

        if ($this->boardClass && class_exists($this->boardClass)) {
            $board = app($this->boardClass);
            if (method_exists($board, 'getFormSchema')) {
                return $board->getFormSchema();
            }
        }

        throw new \Exception('Form schema not found for model class: '.$modelClass);
    }

    public function titleAttribute(): string
    {
        return app($this->boardClass)->getTitleAttribute();
    }

    public function render(): View
    {
        return view('filament.pages.kanban-board.record-component');
    }
}
