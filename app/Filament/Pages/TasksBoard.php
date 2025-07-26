<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\Task as TaskCustomField;
use App\Filament\Adapters\TasksKanbanAdapter;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Models\Task;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;
use UnitEnum;

final class TasksBoard extends KanbanBoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';

    protected static ?string $navigationParentItem = 'Tasks';

    protected static string|null|UnitEnum $navigationGroup = 'Workspace';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    /**
     * The configuration for the Kanban board.
     *
     * @return Builder<Task>
     */
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
            ->schema(fn (Schema $schema): Schema => TaskForm::get($schema))
            ->action(function (Action $action, array $arguments): void {
                /** @var Team $currentTeam */
                $currentTeam = Auth::user()->currentTeam;
                /** @var Task $task */
                $task = $currentTeam->tasks()->create($action->getFormData());
                $task->saveCustomFieldValue($this->statusCustomField(), $arguments['column']);
            });
    }

    public function editAction(Action $action): Action
    {
        return $action->schema(fn (Schema $schema): Schema => TaskForm::get($schema));
    }

    public function getAdapter(): KanbanAdapterInterface
    {
        return new TasksKanbanAdapter(Task::query(), $this->config);
    }

    private function statusCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskCustomField::STATUS)
            ->first();
    }

    /**
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed}>
     */
    private function statuses(): Collection
    {
        return $this->statusCustomField()->options->map(fn (CustomFieldOption $option): array => [
            'id' => $option->getKey(),
            'custom_field_id' => $option->getAttribute('custom_field_id'),
            'name' => $option->getAttribute('name'),
        ]);
    }

    public static function canAccess(): bool
    {
        return (new self)->statusCustomField() instanceof CustomField;
    }
}
