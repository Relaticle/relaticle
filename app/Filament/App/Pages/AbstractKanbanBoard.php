<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Relaticle\CustomFields\Models\CustomField;

abstract class AbstractKanbanBoard extends Page implements HasForms
{
    protected static string $view = 'filament.pages.kanban-board.board';

    abstract protected function getModelClass(): string;

    abstract protected function getStatusFieldCode(): string;

    abstract public function getTitleAttribute(): string;

    abstract public function getDefaultFormData(array $status): array;

    abstract public function createRecord(array $data): Model;

    abstract public function updateRecord(Model $record, array $data): Model;

    #[On('status-changed')]
    public function statusChanged(int $recordId, int $statusId, array $fromOrderedIds, array $toOrderedIds): void
    {
        $this->onStatusChanged($recordId, $statusId, $fromOrderedIds, $toOrderedIds);
    }

    public function onStatusChanged(int $recordId, int $statusId, array $fromOrderedIds, array $toOrderedIds): void
    {
        $this->getEloquentQuery()->find($recordId)->saveCustomFieldValue($this->statusCustomField(), $statusId);

        $modelClass = $this->getModelClass();
        if (method_exists($modelClass, 'setNewOrder')) {
            $modelClass::setNewOrder($toOrderedIds);
        }
    }

    #[On('sort-changed')]
    public function sortChanged(int $recordId, string $statusId, array $orderedIds): void
    {
        $this->onSortChanged($recordId, $statusId, $orderedIds);
    }

    public function onSortChanged(int $recordId, string $statusId, array $orderedIds): void
    {
        $modelClass = $this->getModelClass();
        if (method_exists($modelClass, 'setNewOrder')) {
            $modelClass::setNewOrder($orderedIds);
        }
    }

    /**
     * Get component options for passing to Livewire components
     */
    public function getBoardComponentOptions(): array
    {
        return [
            'modelClass' => $this->getModelClass(),
            'statusFieldCode' => $this->getStatusFieldCode(),
        ];
    }

    protected function statusCustomField(): CustomField
    {
        try {
            return CustomField::query()
                ->forEntity($this->getModelClass())
                ->where('code', $this->getStatusFieldCode())
                ->firstOrFail();
        } catch (\Exception) {
            throw new \Exception('Custom field not found for model class: '.$this->getModelClass().' and code: '.$this->getStatusFieldCode());
        }
    }

    protected function statuses(): Collection
    {
        return $this->statusCustomField()->options->map(fn ($option): array => [
            'id' => $option->id,
            'custom_field_id' => $option->custom_field_id,
            'name' => $option->name,
        ]);
    }

    protected function records(): Collection
    {
        return $this->getEloquentQuery()
            ->ordered()
            ->get();
    }

    #[\Override]
    protected function getViewData(): array
    {
        $records = $this->records();

        $statuses = $this->statuses()
            ->map(function (array $status) use ($records) {
                $status['records'] = $this->filterRecordsByStatus($records, $status);

                return $status;
            });

        return [
            'statuses' => $statuses,
        ];
    }

    protected function filterRecordsByStatus(Collection $records, array $status): array
    {
        if ($records->isEmpty()) {
            return [];
        }

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
        $modelClass = $this->getModelClass();

        return $modelClass::query();
    }
}
