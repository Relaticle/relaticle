<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

final readonly class TextInputComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = TextInput::make("custom_attributes.{$attribute->code}")
            ->maxLength(255)
            ->placeholder(null);

        return $this->configurator->configure($field, $attribute);
    }
}
