<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Illuminate\Database\Eloquent\Model;
use ManukMinasyan\FilamentAttribute\Enums\AttributeLookupTypeEnum;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class SelectComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Select
    {
        $field = Select::make("custom_attributes.{$attribute->code}")
            ->options($attribute->options->pluck('name', 'id')->all())
            ->searchable();

        if ($attribute->lookup_type) {
            $field = $this->configureLookup($field, $attribute->lookup_type);
        }

        /** @var Select */
        return $this->configurator->configure($field, $attribute);
    }

    protected function configureLookup(Select $select, $lookupType): Select
    {
        $lookupMorphedModelPath = Relation::getMorphedModel($lookupType);
        $lookupEntity = new $lookupMorphedModelPath;

        return $select
            ->getSearchResultsUsing(fn (string $search): array => $lookupEntity->query()
                ->whereAny($lookupType->searchColumns(), 'like', "%{$search}%")
                ->limit(50)
                ->pluck($lookupType->labelColumn(), 'id')
                ->toArray())
            ->getOptionLabelUsing(fn ($value): ?string => $lookupEntity::find($value)?->{$lookupType->labelColumn()});
    }
}
