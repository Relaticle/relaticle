<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Task;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Filament\Pages\Concerns\HasEditRecordModal;
use Livewire\Attributes\On;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Filament\Forms;
use Relaticle\CustomFields\Models\CustomField;

class TasksKanbanBoard extends Page implements HasForms
{
    use HasEditRecordModal;

    protected static ?string $navigationLabel = 'By Status';
    protected static ?string $title = 'Tasks By Status';
    protected static ?string $navigationParentItem = 'Tasks';
    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.tasks-kanban.kanban-board';

    protected static string $headerView = 'filament.pages.tasks-kanban.kanban-header';

    protected static string $recordView = 'filament.pages.tasks-kanban.kanban-record';

    protected static string $statusView = 'filament.pages.tasks-kanban.kanban-status';
    protected static string $editModalView = 'filament.pages.tasks-kanban.components.edit-record-modal';

    protected static string $scriptsView = 'filament.pages.tasks-kanban.kanban-scripts';

    protected static string $model = Task::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'status';

    protected function getEditModalFormSchema(null|int $recordId): array
    {
        return [
            Forms\Components\TextInput::make('title')->required(),
            CustomFieldsComponent::make()
        ];
    }

    private function statusCustomField()
    {
        return CustomField::query()
            ->forEntity(self::$model)
            ->where('code', 'status')
            ->firstOrFail();
    }

    protected function statuses(): Collection
    {
        return $this->statusCustomField()->options->map(function ($option) {
            return [
                'id' => $option->id,
                'custom_field_id' => $option->custom_field_id,
                'name' => $option->name,
            ];
        });
    }

    protected function records(): Collection
    {
        return $this->getEloquentQuery()
            ->ordered()
            ->get();
    }

    protected function getViewData(): array
    {
        $records = $this->records();
        $statuses = $this->statuses()
            ->map(function ($status) use ($records) {
                $status['records'] = $this->filterRecordsByStatus($records, $status);

                return $status;
            });

        return [
            'statuses' => $statuses,
        ];
    }

    protected function filterRecordsByStatus(Collection $records, array $status): array
    {
        return $records->toQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($status): void {
                $builder->where('custom_field_values.custom_field_id', $status['custom_field_id'])
                    ->where('custom_field_values.'.$this->statusCustomField()->getValueColumn(), $status['id']);
            })
            ->ordered()
            ->get()
            ->all();
    }

    protected function getEloquentQuery(): Builder
    {
        return static::$model::query();
    }

    #[On('status-changed')]
    public function statusChanged(int $recordId, int $statusId, array $fromOrderedIds, array $toOrderedIds): void
    {
        $this->onStatusChanged($recordId, $statusId, $fromOrderedIds, $toOrderedIds);
    }

    public function onStatusChanged(int $recordId, int $statusId, array $fromOrderedIds, array $toOrderedIds): void
    {
        $this->getEloquentQuery()->find($recordId)->saveCustomFieldValue($this->statusCustomField(), $statusId);

        if (method_exists(static::$model, 'setNewOrder')) {
            static::$model::setNewOrder($toOrderedIds);
        }
    }

    #[On('sort-changed')]
    public function sortChanged(int $recordId, string $statusId, array $orderedIds): void
    {
        $this->onSortChanged($recordId, $statusId, $orderedIds);
    }

    public function onSortChanged(int $recordId, string $statusId, array $orderedIds): void
    {
        if (method_exists(static::$model, 'setNewOrder')) {
            static::$model::setNewOrder($orderedIds);
        }
    }
}
