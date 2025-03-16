<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class TasksBoard extends AbstractKanbanBoard implements HasForms
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';

    protected static ?string $navigationParentItem = 'Tasks';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected function getModelClass(): string
    {
        return Task::class;
    }

    public function getTitleAttribute(): string
    {
        return 'title';
    }

    protected function getStatusFieldCode(): string
    {
        return 'status';
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->required()
                ->placeholder('Enter task title')
                ->rules(['max:255']),
            Forms\Components\Select::make('assignees')
                ->relationship('assignees', 'name')
                ->multiple()
                ->preload()
                ->label('Assign to'),
            CustomFieldsComponent::make()->model($this->getModelClass()),
        ];
    }


    /**
     * Get the default form data for a new record
     */
    public function getDefaultFormData(array $status): array
    {
        return [
            'custom_fields' => [
                $this->getStatusFieldCode() => $status['id'],
            ],
        ];
    }

    /**
     * Create a new record with the given data
     */
    public function createRecord(array $data): Model
    {
        return auth()->user()->currentTeam->tasks()->create($data);
    }

    public function updateRecord($record, array $data): Model
    {
        $record->update($data);

        return $record;
    }
}
