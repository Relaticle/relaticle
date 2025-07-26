<?php

declare(strict_types=1);

namespace App\Filament\Adapters;

use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

final class OpportunitiesKanbanAdapter extends DefaultKanbanAdapter
{
    /**
     * @return Collection<int, mixed>
     */
    public function getItemsForColumn(string|int $columnId, int $limit = 50): Collection
    {
        $orderField = $this->config->getOrderField();

        $query = $this->newQuery()
            ->with(['company', 'contact'])
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId): void {
                $builder->where('custom_field_values.custom_field_id', $this->stageCustomField()->id)
                    ->where('custom_field_values.'.$this->stageCustomField()->getValueColumn(), $columnId);
            });

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->limit(50)->get();

        return $this->formatCardsForDisplay($models);
    }

    public function getColumnItemsCount(string|int $columnId): int
    {
        return $this->newQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId): void {
                $builder->where('custom_field_values.custom_field_id', $this->stageCustomField()->id)
                    ->where('custom_field_values.'.$this->stageCustomField()->getValueColumn(), $columnId);
            })
            ->count();
    }

    public function updateRecordsOrderAndColumn(string|int $columnId, array $recordIds): bool
    {
        Opportunity::query()
            ->whereIn('id', $recordIds)
            ->each(function (Opportunity $model) use ($columnId): void {
                $model->saveCustomFieldValue($this->stageCustomField(), $columnId);
            });

        Opportunity::setNewOrder($recordIds);

        return true;
    }

    private function stageCustomField(): CustomField
    {
        /** @var CustomField */
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', 'stage')
            ->firstOrFail();
    }
}
