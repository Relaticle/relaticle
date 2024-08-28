<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;

final readonly class DateComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = DatePicker::make("custom_attributes.{$attribute->code}");

        return $this->configurator->configure($field, $attribute);
    }
}
