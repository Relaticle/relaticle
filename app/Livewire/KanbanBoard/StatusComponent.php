<?php

declare(strict_types=1);

namespace App\Livewire\KanbanBoard;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

final class StatusComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $status;
    public string $modelClass;
    public ?string $boardClass = null;

    #[On('record-created')]
    public function handleRecordCreated(int|string $recordId, int|string $statusId): void
    {
        if ((int)$this->status['id'] === $statusId) {
            $this->status['records'][] = app($this->modelClass)->find($recordId);
        }
    }

    public function render(): View
    {
        return view('filament.pages.kanban-board.status-component');
    }
}
