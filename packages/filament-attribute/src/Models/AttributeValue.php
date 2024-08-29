<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use ManukMinasyan\FilamentAttribute\Enums\AttributeTypeEnum;

/**
 * @property Attribute $attribute
 */
final class AttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attribute_id',
        'text_value',
        'integer_value',
        'float_value',
        'json_value',
        'boolean_value',
        'date_value',
        'datetime_value',
    ];

    protected $casts = [
        'json_value' => 'collection',
    ];

    /**
     * @var array<string, string>
     */
    public static array $attributeTypeFields = [
        AttributeTypeEnum::TEXT->value => 'text_value',
        AttributeTypeEnum::TEXTAREA->value => 'text_value',
        AttributeTypeEnum::SELECT->value => 'integer_value',
        AttributeTypeEnum::PRICE->value => 'float_value',
        AttributeTypeEnum::MULTISELECT->value => 'json_value',
        AttributeTypeEnum::TOGGLE->value => 'boolean_value',
        AttributeTypeEnum::DATE->value => 'date_value',
        AttributeTypeEnum::DATETIME->value => 'datetime_value',
    ];

    /**
     * @return BelongsTo<Attribute, AttributeValue>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * @return MorphTo<Model, AttributeValue>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function getValue(): mixed
    {
        $column = $this->getValueColumn();

        return $this->$column;
    }

    public function setValue(mixed $value): void
    {
        $column = $this->getValueColumn();
        $this->$column = $value;
    }

    public function getValueColumn(): string
    {
        $attributeType = $this->attribute->type->value;

        return self::$attributeTypeFields[$attributeType]
            ?? throw new \InvalidArgumentException("Unsupported attribute type: {$attributeType}");
    }
}
