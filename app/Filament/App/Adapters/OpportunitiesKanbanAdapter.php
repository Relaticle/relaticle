<?php

declare(strict_types=1);

namespace App\Filament\App\Adapters;

use App\Models\Opportunity;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

final class OpportunitiesKanbanAdapter extends DefaultKanbanAdapter
{
    public function getCreateForm(Form $form, mixed $currentColumn): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->placeholder('Enter opportunity title')
                ->columnSpanFull(),
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('contact_id')
                ->relationship('contact', 'name')
                ->preload(),
            CustomFieldsComponent::make()->columnSpanFull(),
        ]);
    }

    public function createRecord(array $attributes, mixed $currentColumn): ?Model
    {
        $opportunity = Auth::user()->currentTeam->opportunities()->create($attributes);

        $opportunity->saveCustomFieldValue($this->stageCustomField(), $currentColumn);

        return $opportunity;
    }

    public function getEditForm(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->placeholder('Enter opportunity title')
                ->columnSpanFull(),
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('contact_id')
                ->relationship('contact', 'name')
                ->preload(),
            CustomFieldsComponent::make(),
        ]);
    }

    /**
     * Update an existing card with the given attributes.
     *
     * @param  Opportunity|Model  $record  The card to update
     * @param  array<string, mixed>  $attributes  The card attributes to update
     */
    public function updateRecord(Opportunity|Model $record, array $attributes): bool
    {
        if (isset($attributes['stage'])) {
            $record->saveCustomFieldValue(
                $this->stageCustomField(),
                $attributes['stage'],
            );
        }

        unset($attributes['stage']);
        unset($attributes['description']);
        $record->fill($attributes);

        return $record->save();
    }

    public function getItemsForColumn(string|int $columnId, int $limit = 50): Collection
    {
        $orderField = $this->config->getOrderField();

        $query = $this->newQuery()
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

    protected function stageCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', 'stage')
            ->firstOrFail();
    }
}
