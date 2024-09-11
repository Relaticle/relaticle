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
    public function __construct($attributes = [])
    {
        // Ensure custom attributes are included in a fillable array
        $this->fillable = array_merge(['custom_attributes'], $this->fillable);
        parent::__construct($attributes);
    }

    /**
     * @var array<int, array<string, mixed>>
     */
    protected static array $tempCustomAttributes = [];

    protected static function bootUsesCustomAttributes(): void
    {
        static::saving(function (Model $model): void {
            $model->handleCustomAttributes();
        });

        static::saved(function (Model $model): void {
            $model->saveCustomAttributesFromTemp();
        });
    }

    /**
     * Handle the custom attributes before saving the model.
     */
    protected function handleCustomAttributes(): void
    {
        if (! empty($this->custom_attributes) && is_array($this->custom_attributes)) {
            self::$tempCustomAttributes[spl_object_id($this)] = $this->custom_attributes;
            unset($this->custom_attributes);
        }
    }

    /**
     * Save custom attributes from temporary storage after the model is created/updated.
     */
    protected function saveCustomAttributesFromTemp(): void
    {
        $objectId = spl_object_id($this);

        if (isset(self::$tempCustomAttributes[$objectId]) && method_exists($this, 'saveCustomAttributes')) {
            $this->saveCustomAttributes(self::$tempCustomAttributes[$objectId]);
            unset(self::$tempCustomAttributes[$objectId]);
        }
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
