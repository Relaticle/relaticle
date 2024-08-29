<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use ManukMinasyan\FilamentAttribute\Models\AttributeValue;
use ManukMinasyan\FilamentAttribute\Models\Contracts\HasCustomAttributes;

/**
 * @see HasCustomAttributes
 */
trait UsesCustomAttributes
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected static array $tempCustomAttributes = [];

    protected static function bootUsesCustomAttributes(): void
    {
        static::creating(function (Model $model): void {
            if (! empty($model->custom_attributes) && is_array($model->custom_attributes)) {
                self::$tempCustomAttributes[spl_object_id($model)] = $model->custom_attributes;
                unset($model->custom_attributes);
            }
        });

        static::created(function (Model $model): void {
            $objectId = spl_object_id($model);
            // TODO: Remove the method exists condition and fix type problem with another way
            if (isset(self::$tempCustomAttributes[$objectId]) && method_exists($model, 'saveCustomAttributes')) {
                $customAttributes = self::$tempCustomAttributes[$objectId];
                $model->saveCustomAttributes($customAttributes);
                unset(self::$tempCustomAttributes[$objectId]);
            }
        });

        static::saving(function (Model $model): void {
            if (! empty($model->custom_attributes) && is_array($model->custom_attributes) && method_exists($model, 'saveCustomAttributes')) {
                $model->saveCustomAttributes($model->custom_attributes);
                unset($model->custom_attributes);
            }
        });
    }

    /**
     * @return Builder<Attribute>
     */
    public function customAttributes(): Builder
    {
        return Attribute::query()->forEntity($this->getMorphClass());
    }

    /**
     * @return MorphMany<AttributeValue>
     */
    public function customAttributeValues(): MorphMany
    {
        return $this->morphMany(AttributeValue::class, 'entity');
    }

    public function getCustomAttributeValue(string $code): mixed
    {
        $attribute = $this->customAttributes()->where('code', $code)->first();

        if (! $attribute) {
            return null;
        }

        $attributeValue = $this->customAttributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();

        $attributeValue = $attributeValue ? $attributeValue->getValue() : null;

        return $attributeValue instanceof Collection ? $attributeValue->toArray() : $attributeValue;
    }

    public function saveCustomAttributeValue(string $code, mixed $value): void
    {
        $attribute = $this->customAttributes()->where('code', $code)->firstOrFail();

        $attributeValue = $this->customAttributeValues()->firstOrNew(['attribute_id' => $attribute->id]);

        $attributeValue->setValue($value);
        $attributeValue->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveCustomAttributes(array $attributes): void
    {
        $this->customAttributes()->each(function (Attribute $attribute) use ($attributes): void {
            $value = $attributes[$attribute->code] ?? null;
            $this->saveCustomAttributeValue($attribute->code, $value);
        });
    }
}
