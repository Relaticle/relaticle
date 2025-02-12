<?php

namespace App\Filament\Pages;

use App\Enums\TaskStatus;
use App\Models\Task;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Filament\Forms;

class TasksKanbanBoard extends KanbanBoard
{
    protected static string $model = Task::class;
    protected static string $statusEnum = TaskStatus::class;

    protected static ?string $navigationLabel = 'By Status';
    protected static ?string $title = 'Tasks By Status';
    protected static ?string $navigationParentItem = 'Tasks';
    protected static ?string $navigationGroup = 'Workspace';


    protected function getEditModalFormSchema(null|int $recordId): array
    {
        return [
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('status')->required(),
            CustomFieldsComponent::make()
        ];
    }
}
