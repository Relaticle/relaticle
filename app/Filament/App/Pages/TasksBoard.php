<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\CustomFields\Task as TaskCustomField;
use App\Filament\App\Adapters\TasksKanbanAdapter;
use App\Filament\App\Resources\TaskResource\Forms\TaskForm;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

final class TasksBoard extends KanbanBoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';

    protected static ?string $navigationParentItem = 'Tasks';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public function getSubject(): Builder
    {
        return Task::query();
    }

    public function mount(): void
    {
        $this->titleField('title')
            ->columnField('status')
            ->descriptionField('description')
            ->orderField('order_column')
            ->columns($this->statuses()->pluck('name', 'id')->toArray())
            ->columnColors()
            ->cardLabel('Task');
    }

    public function createAction(Action $action): Action
    {
        return $action
            ->slideOver(false)
            ->modalWidth('2xl')
            ->iconButton()
            ->icon('heroicon-o-plus')
            ->form(fn (Forms\Form $form): \Filament\Forms\Form => TaskForm::get($form))
            ->action(function (Action $action, array $arguments): void {
                $task = Auth::user()->currentTeam->tasks()->create($action->getFormData());
                $task->saveCustomFieldValue($this->statusCustomField(), $arguments['column']);
            });
    }

    public function editAction(Action $action): Action
    {
        return $action->form(fn (Forms\Form $form): \Filament\Forms\Form => TaskForm::get($form));
    }

    public function getAdapter(): KanbanAdapterInterface
    {
        return new TasksKanbanAdapter(Task::query(), $this->config);
    }

    private function statusCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskCustomField::STATUS)
            ->first();
    }

    private function statuses(): Collection
    {
        return $this->statusCustomField()->options->map(fn ($option): array => [
            'id' => $option->id,
            'custom_field_id' => $option->custom_field_id,
            'name' => $option->name,
        ]);
    }
}
