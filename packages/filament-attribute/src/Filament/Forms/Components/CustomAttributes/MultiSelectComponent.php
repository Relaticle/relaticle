<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Relations\Relation;
use ManukMinasyan\FilamentAttribute\Enums\AttributeLookupTypeEnum;
use ManukMinasyan\FilamentAttribute\Models\Attribute;

final readonly class MultiSelectComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = (new SelectComponent($this->configurator))->make($attribute)->multiple();

        return $attribute->lookup_type instanceof AttributeLookupTypeEnum
            ? $this->configureLookupForMultiSelect($field, $attribute->lookup_type)
            : $field;
    }

    protected function configureLookupForMultiSelect(Select $select, AttributeLookupTypeEnum $lookupType): Select
    {
        $lookupMorphedModelPath = Relation::getMorphedModel($lookupType->value);
        $lookupEntity = new $lookupMorphedModelPath;

        return $select->getOptionLabelsUsing(fn (array $values): array => $lookupEntity::query()
            ->whereIn('id', $values)
            ->pluck($lookupType->labelColumn(), 'id')
            ->toArray());
    }
}
