<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\CustomFields\Task as TaskCustomField;
use App\Filament\App\Adapters\TasksKanbanAdapter;
use App\Models\Task;
use Illuminate\Support\Collection;
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

    public function mount(): void
    {
        $this->titleField('title')
            ->columnField('status')
            ->descriptionField('description')
            ->orderField('order_column')
            ->columns($this->statuses()->pluck('name', 'id')->toArray())
            ->columnColors();
    }

    public function getAdapter(): KanbanAdapterInterface
    {
        return new TasksKanbanAdapter(Task::query(), $this->config);
    }

    protected function statusCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskCustomField::STATUS)
            ->first();
    }

    protected function statuses(): Collection
    {
        return $this->statusCustomField()->options->map(fn ($option): array => [
            'id' => $option->id,
            'custom_field_id' => $option->custom_field_id,
            'name' => $option->name,
        ]);
    }
}
