<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\Task as TaskCustomField;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Models\Task;
use App\Models\Team;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use UnitEnum;

final class TasksBoard extends BoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';

    protected static ?string $navigationParentItem = 'Tasks';

    protected static string|null|UnitEnum $navigationGroup = 'Workspace';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    /**
     * Configure the board using the new Filament V4 architecture.
     */
    public function board(Board $board): Board
    {
        return $board
            ->query(
                Task::query()
                    ->join('custom_field_values as cfv', function ($join) {
                        $join->on('tasks.id', '=', 'cfv.entity_id')
                            ->where('cfv.custom_field_id', '=', $this->statusCustomField()->getKey());
                    })
            )
            ->recordTitleAttribute('title')
            ->columnIdentifier('cfv.integer_value')
            ->positionIdentifier('order_column')
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema) {
                return $schema->components([
                    CardFlex::make([
                        CustomFields::infolist()
                            ->forSchema($schema)
                            ->except(['status'])
                            ->build()
                            ->columnSpanFull(),
                    ]),
                ]);
            })
            ->columnActions([
                CreateAction::make()
                    ->label('Add Task')
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Task::class)
                    ->schema(fn (Schema $schema) => TaskForm::get($schema, ['status']))
                    ->using(function (array $data, array $arguments): Task {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::user()->currentTeam;

                        /** @var Task $task */
                        $task = $currentTeam->tasks()->create($data);

                        $statusField = $this->statusCustomField();
                        $task->saveCustomFieldValue($statusField, $arguments['column']);

                        return $task;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label('Edit')
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(fn (Schema $schema) => TaskForm::get($schema, ['status']))
                    ->fillForm(fn (Task $record): array => [
                        'title' => $record->title,
                        'description' => $record->description,
                        'priority' => $record->priority,
                        'assigned_to' => $record->assigned_to,
                    ])
                    ->action(function (Task $record, array $data): void {
                        $record->update($data);
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Task $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('assignees')
                    ->label('Assignee')
                    ->relationship('assignees', 'name')
                    ->multiple(),
            ])
            ->filtersFormWidth(Width::Medium);
    }

    public function moveCard(string $cardId, string $targetColumnId, ?string $afterCardId = null, ?string $beforeCardId = null): void {}

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     *
     * @throws Exception
     */
    private function getColumns(): array
    {
        return $this->statuses()->map(fn (array $status) => Column::make((string) $status['id'])
            ->color($status['color'])
            ->label($status['name'])
        )->toArray();
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
            'color' => $option->settings->color,
        ]);
    }
}
