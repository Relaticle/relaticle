<?php

declare(strict_types=1);

namespace App\Livewire\TasksBoard;

use App\Models\Task;
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

    #[On('task-created')]
    public function handleTaskCreated(Task $task, int $statusId): void
    {
        // Only add to this component if the task belongs to this status
        if ((int)$this->status['id'] === $statusId) {
            $this->status['records'][] = $task;
        }
    }

    public function render(): View
    {
        return view('filament.pages.tasks-board.status-component');
    }
}
