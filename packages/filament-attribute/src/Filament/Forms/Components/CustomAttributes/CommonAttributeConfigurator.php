<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Illuminate\Database\Eloquent\Model;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\Field;

final readonly class CommonAttributeConfigurator
{
    /**
     * @template T of Field
     *
     * @param  T  $field
     * @return T
     */
    public function configure(Field $field, Attribute $attribute): Field
    {
        return $field
            ->label($attribute->name)
            ->required((bool)$attribute->is_required)
            ->afterStateHydrated(function ($component, $state, $record) use ($attribute): void {
                $component->state($record?->getCustomAttributeValue($attribute->code));
            })
            ->dehydrated(fn ($state): bool => $state !== null && $state !== '')
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) use ($attribute): void {
                $this->validateAttributeValue($state, $attribute, $set);
            });
    }

    protected function validateAttributeValue(mixed $state, Attribute $attribute, callable $set): void
    {
        // Implement common validation logic here
    }
}
