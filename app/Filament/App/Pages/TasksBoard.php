<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Task;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Relaticle\CustomFields\Models\CustomField;

class TasksBoard extends Page implements HasForms
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Tasks';
    protected static ?string $navigationParentItem = 'Tasks';
    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.tasks-board.board';

    protected static string $scriptsView = 'filament.pages.tasks-board.board-scripts';

    protected static string $model = Task::class;


    protected static string $recordStatusAttribute = 'status';


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
                    ->where('custom_field_values.' . $this->statusCustomField()->getValueColumn(), $status['id']);
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
