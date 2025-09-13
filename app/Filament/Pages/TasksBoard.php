<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\TaskField as TaskCustomField;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Models\Task;
use App\Models\Team;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Exception\InvalidArgumentException;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use Throwable;
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
                    ->leftJoin('custom_field_values as cfv', function (\Illuminate\Database\Query\JoinClause $join): void {
                        $join->on('tasks.id', '=', 'cfv.entity_id')
                            ->where('cfv.custom_field_id', '=', $this->statusCustomField()->getKey());
                    })
                    ->select('tasks.*', 'cfv.integer_value')
            )
            ->recordTitleAttribute('title')
            ->columnIdentifier('cfv.integer_value')
            ->positionIdentifier('order_column')
            ->searchable(['title'])
            ->columns($this->getColumns())
            ->cardSchema(function (Schema $schema): Schema {
                $descriptionCustomField = CustomFields::infolist()
                    ->forSchema($schema)
                    ->only(['description'])
                    ->hiddenLabels()
                    ->visibleWhenFilled()
                    ->withoutSections()
                    ->values()
                    ->first()
                    ?->columnSpanFull()
                    ->visible(fn (mixed $state): bool => filled($state))
                    ->formatStateUsing(fn (string $state): string => str($state)->stripTags()->limit()->toString());

                return $schema->components([
                    CardFlex::make([
                        $descriptionCustomField,
                    ]),
                    ImageEntry::make('assignees.profile_photo_url')
                        ->hiddenLabel()
                        ->alignLeft()
                        ->imageHeight(24)
                        ->circular()
                        ->visible(fn (mixed $state): bool => filled($state))
                        ->stacked(),
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
                    ->schema(fn (Schema $schema): Schema => TaskForm::get($schema, ['status']))
                    ->using(function (array $data, array $arguments): Task {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::guard('web')->user()->currentTeam;

                        /** @var Task $task */
                        $task = $currentTeam->tasks()->create($data);

                        $statusField = $this->statusCustomField();
                        $task->saveCustomFieldValue($statusField, $arguments['column']);
                        $task->order_column = $this->getBoardPositionInColumn((string) $arguments['column']);

                        return $task;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label('Edit')
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(fn (Schema $schema): Schema => TaskForm::get($schema))
                    ->fillForm(fn (Task $record): array => $record->toArray())
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

    /**
     * Move card to new position using Rank-based positioning.
     *
     * @throws Throwable
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null
    ): void {
        $board = $this->getBoard();
        $query = $board->getQuery();

        if (! $query instanceof \Illuminate\Database\Eloquent\Builder) {
            throw new InvalidArgumentException('Board query not available');
        }

        /** @var Task|null $card */
        $card = (clone $query)->find($cardId);
        if (! $card) {
            throw new InvalidArgumentException("Card not found: {$cardId}");
        }

        // Calculate new position using Rank service
        $newPosition = $this->calculatePositionBetweenCards($afterCardId, $beforeCardId, $targetColumnId);

        // Use transaction for data consistency
        DB::transaction(function () use ($card, $board, $targetColumnId, $newPosition): void {
            $columnIdentifier = $board->getColumnIdentifierAttribute();
            $columnValue = $this->resolveStatusValue($card, $columnIdentifier, $targetColumnId);
            $positionIdentifier = $board->getPositionIdentifierAttribute();

            $card->update([$positionIdentifier => $newPosition]);

            $card->saveCustomFieldValue($this->statusCustomField(), $columnValue);
        });

        // Emit success event after successful transaction
        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     *
     * @throws Exception
     */
    private function getColumns(): array
    {
        return $this->statuses()->map(fn (array $status): Column => Column::make((string) $status['id'])
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
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed, color: string}>
     */
    private function statuses(): Collection
    {
        $field = $this->statusCustomField();

        if (! $field instanceof CustomField) {
            return collect();
        }

        // Check if color options are enabled for this field
        $colorsEnabled = $field->settings->enable_option_colors ?? false;

        return $field->options->map(fn (CustomFieldOption $option): array => [
            'id' => $option->getKey(),
            'custom_field_id' => $option->getAttribute('custom_field_id'),
            'name' => $option->getAttribute('name'),
            'color' => $colorsEnabled ? ($option->settings->color ?? 'gray') : 'gray',
        ]);
    }

    public static function canAccess(): bool
    {
        return (new self)->statusCustomField() instanceof CustomField;
    }
}
