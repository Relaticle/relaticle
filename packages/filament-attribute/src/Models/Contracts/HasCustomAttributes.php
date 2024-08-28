<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Models\Contracts;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use ManukMinasyan\FilamentAttribute\Models\AttributeValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasCustomAttributes
{
    /**
     * @return Builder<Attribute>
     */
    public function customAttributes(): Builder;

    /**
     * @return MorphMany<AttributeValue>
     */
    public function customAttributeValues(): MorphMany;

    public function getCustomAttributeValue(string $code): mixed;

    public function saveCustomAttributeValue(string $code, mixed $value): void;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveCustomAttributes(array $attributes): void;
}
