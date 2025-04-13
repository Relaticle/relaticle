<?php

declare(strict_types=1);

namespace App\Filament\App\Adapters;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

class TasksKanbanAdapter extends DefaultKanbanAdapter
{
    /**
     * @param string|int $columnId
     * @param int $limit
     * @return Collection
     */
    public function getItemsForColumn(string|int $columnId, int $limit = 50): Collection
    {
        $orderField = $this->config->getOrderField();

        $query = $this->newQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId): void {
                $builder->where('custom_field_values.custom_field_id', $this->statusCustomField()->id)
                    ->where('custom_field_values.' . $this->statusCustomField()->getValueColumn(), $columnId);
            });

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->limit(50)->get();

        return $this->formatCardsForDisplay($models);
    }

    /**
     * @param string|int $columnId
     * @return int
     */
    public function getColumnItemsCount(string|int $columnId): int
    {
        return $this->newQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId): void {
                $builder->where('custom_field_values.custom_field_id', $this->statusCustomField()->id)
                    ->where('custom_field_values.' . $this->statusCustomField()->getValueColumn(), $columnId);
            })
            ->count();
    }

    /**
     * @param string|int $columnId
     * @param array $recordIds
     * @return bool
     */
    public function updateRecordsOrderAndColumn(string|int $columnId, array $recordIds): bool
    {
        Task::query()
            ->whereIn('id', $recordIds)
            ->each(function (Task $model) use ($columnId): void {
                $model->saveCustomFieldValue($this->statusCustomField(), $columnId);
            });

        Task::setNewOrder($recordIds);

        return true;
    }

    /**
     * @return CustomField
     */
    protected function statusCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', 'status')
            ->firstOrFail();
    }
}
