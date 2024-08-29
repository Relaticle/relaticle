<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Filament\Forms\Components\Field;
use ManukMinasyan\FilamentAttribute\Models\Attribute;

interface AttributeComponentInterface
{
    public function make(Attribute $attribute): Field;
}
