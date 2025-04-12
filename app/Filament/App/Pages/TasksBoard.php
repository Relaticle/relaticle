<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Adapters\TasksKanbanAdapter;
use App\Filament\App\Resources\TaskResource\Forms\TaskForm;
use App\Models\Task;
use App\Enums\CustomFields\Task as TaskCustomField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;
use Filament\Actions\Action;
use Filament\Forms;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

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

    /**
     * @return void
     */
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

    /**
     * @param Action $action
     * @return Action
     */
    public function createAction(Action $action): Action
    {
        return $action
            ->slideOver(false)
            ->modalWidth('2xl')
            ->iconButton()
            ->icon('heroicon-o-plus')
            ->form(function (Forms\Form $form) {
                return TaskForm::get($form);
            })
            ->action(function (Action $action, array $arguments): void {
                $task = Auth::user()->currentTeam->tasks()->create($action->getFormData());
                $task->saveCustomFieldValue($this->statusCustomField(), $arguments['column']);
            });
    }

    /**
     * @param Action $action
     * @return Action
     */
    public function editAction(Action $action): Action
    {
        return $action->form(function (Forms\Form $form) {
            return TaskForm::get($form);
        });
    }

    /**
     * @return KanbanAdapterInterface
     */
    public function getAdapter(): KanbanAdapterInterface
    {
        return new TasksKanbanAdapter(Task::query(), $this->config);
    }

    /**
     * @return CustomField
     */
    protected function statusCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskCustomField::STATUS)
            ->first();
    }

    /**
     * @return Collection
     */
    protected function statuses(): Collection
    {
        return $this->statusCustomField()->options->map(fn($option): array => [
            'id' => $option->id,
            'custom_field_id' => $option->custom_field_id,
            'name' => $option->name,
        ]);
    }
}
