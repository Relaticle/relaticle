<?php

declare(strict_types=1);

namespace App\Filament\Adapters;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

final class TasksKanbanAdapter extends DefaultKanbanAdapter
{
    /**
     * @return Collection<int, mixed>
     */
    public function getItemsForColumn(string|int $columnId, int $limit = 50): Collection
    {
        $orderField = $this->config->getOrderField();
        $statusField = $this->statusCustomField();

        // If no status field exists, return empty collection
        if (!$statusField) {
            return collect();
        }

        $query = $this->newQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId, $statusField): void {
                $builder->where('custom_field_values.custom_field_id', $statusField->id)
                    ->where('custom_field_values.integer_value', $columnId);
            });

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->limit($limit)->get();

        return $this->formatCardsForDisplay($models);
    }

    public function getColumnItemsCount(string|int $columnId): int
    {
        $statusField = $this->statusCustomField();

        // If no status field exists, return 0
        if (!$statusField) {
            return 0;
        }

        return $this->newQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId, $statusField): void {
                $builder->where('custom_field_values.custom_field_id', $statusField->id)
                    ->where('custom_field_values.integer_value', $columnId);
            })
            ->count();
    }

    public function updateRecordsOrderAndColumn(string|int $columnId, array $recordIds): bool
    {
        $statusField = $this->statusCustomField();

        // If no status field exists, just update order
        if (!$statusField) {
            Task::setNewOrder($recordIds);
            return true;
        }

        Task::query()
            ->whereIn('id', $recordIds)
            ->each(function (Task $model) use ($columnId, $statusField): void {
                $model->saveCustomFieldValue($statusField, $columnId);
            });

        Task::setNewOrder($recordIds);

        return true;
    }

    private function statusCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->where('entity_type', 'task')
            ->where('code', 'status')
            ->first();
    }
}
