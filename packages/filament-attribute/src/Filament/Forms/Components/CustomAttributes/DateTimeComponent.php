<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;

final readonly class DateTimeComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = DateTimePicker::make("custom_attributes.{$attribute->code}");

        return $this->configurator->configure($field, $attribute);
    }
}
