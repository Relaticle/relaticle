<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final readonly class PriceComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = TextInput::make("custom_attributes.{$attribute->code}")
            ->prefix('$')
            ->numeric()
            ->inputMode('decimal')
            ->step(0.01)
            ->minValue(0)
            ->default(0)
            ->rules(['numeric', 'min:0'])
            ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
            ->dehydrateStateUsing(fn ($state) => Str::of($state)->replace(['$', ','], '')->toFloat());

        return $this->configurator->configure($field, $attribute);
    }
}
