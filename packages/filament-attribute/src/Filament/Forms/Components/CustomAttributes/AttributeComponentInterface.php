<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\Field;

interface AttributeComponentInterface
{
    public function make(Attribute $attribute): Field;
}
