<?php

declare(strict_types=1);

namespace App\Filament\App\Adapters;

use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

final class TasksKanbanAdapter extends DefaultKanbanAdapter
{
    public function getCreateForm(Form $form, mixed $currentColumn): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            CustomFieldsComponent::make()->columnSpanFull(),
        ]);
    }

    public function createRecord(array $attributes, mixed $currentColumn): ?Model
    {
        $opportunity = Auth::user()->currentTeam->opportunities()->create($attributes);

        $opportunity->saveCustomFieldValue($this->statusCustomField(), $currentColumn);

        return $opportunity;
    }

    public function getEditForm(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('assignees')
                ->multiple()
                ->relationship('assignees', 'name'),
            CustomFieldsComponent::make(),
        ]);
    }

    public function getItemsForColumn(string|int $columnId, int $limit = 50): Collection
    {
        $orderField = $this->config->getOrderField();

        $query = $this->newQuery()
            ->whereHas('customFieldValues', function (Builder $builder) use ($columnId): void {
                $builder->where('custom_field_values.custom_field_id', $this->statusCustomField()->id)
                    ->where('custom_field_values.'.$this->statusCustomField()->getValueColumn(), $columnId);
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
                $builder->where('custom_field_values.custom_field_id', $this->statusCustomField()->id)
                    ->where('custom_field_values.'.$this->statusCustomField()->getValueColumn(), $columnId);
            })
            ->count();
    }

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

    protected function statusCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Task::class)
            ->where('code', 'status')
            ->firstOrFail();
    }
}
