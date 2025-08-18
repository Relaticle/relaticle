<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\Task as TaskCustomField;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Models\Task;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Property;
use UnitEnum;

final class TasksBoard extends BoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';

    protected static ?string $navigationParentItem = 'Tasks';

    protected static string|null|UnitEnum $navigationGroup = 'Workspace';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    /**
     * Get the Eloquent query for the board.
     *
     * @return Builder<Task>
     */
    public function getEloquentQuery(): Builder
    {
        return Task::query();
    }

    /**
     * Configure the board using the new Filament V4 architecture.
     */
    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->cardTitle('title')
            ->columnIdentifier('status') // This will need to be mapped to custom field
            ->description('description')
            ->defaultSort('order_column')
            ->columns($this->getColumns())
            ->cardProperties($this->getCardProperties())
            ->columnActions([
                CreateAction::make()
                    ->slideOver(false)
                    ->modalWidth('2xl')
                    ->iconButton()
                    ->icon('heroicon-o-plus')
                    ->model(Task::class)
                    ->schema(fn (Schema $schema): Schema => TaskForm::get($schema))
                    ->action(function (CreateAction $action, array $arguments): void {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::user()->currentTeam;
                        /** @var Task $task */
                        $task = $currentTeam->tasks()->create($action->getFormData());
                        $task->saveCustomFieldValue($this->statusCustomField(), $arguments['column']);
                    }),
            ])
            ->cardActions([
                // Temporarily disabled to focus on column actions first
                // EditAction::make()
                //     ->model(Task::class)
                //     ->schema(fn (Schema $schema): Schema => TaskForm::get($schema)),
                // DeleteAction::make()
                //     ->model(Task::class),
            ]);
    }

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     */
    private function getColumns(): array
    {
        $columns = [];
        foreach ($this->statuses() as $status) {
            $columns[] = Column::make((string) $status['id'])
                ->label($status['name']);
        }
        return $columns;
    }

    /**
     * Get card properties for the board.
     *
     * @return array<Property>
     */
    private function getCardProperties(): array
    {
        return [
            Property::make('title')
                ->label('Task Title'),
            Property::make('description')
                ->label('Description'),
            // Add more properties as needed
        ];
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
