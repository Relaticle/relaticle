<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use ManukMinasyan\FilamentAttribute\Models\Attribute;

final readonly class TextareaAttributeComponent implements AttributeComponentInterface
{
    public function __construct(private CommonAttributeConfigurator $configurator) {}

    public function make(Attribute $attribute): Field
    {
        $field = Textarea::make("custom_attributes.{$attribute->code}")
            ->rows(3)
            ->maxLength(50000)
            ->placeholder(null);

        return $this->configurator->configure($field, $attribute);
    }
}
