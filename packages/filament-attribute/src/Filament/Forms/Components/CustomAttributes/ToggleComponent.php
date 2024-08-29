<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use ManukMinasyan\FilamentAttribute\Models\Attribute;

final readonly class ToggleComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = Toggle::make("custom_attributes.{$attribute->code}")
            ->onColor('success')
            ->offColor('danger');

        return $this->configurator->configure($field, $attribute);
    }
}
