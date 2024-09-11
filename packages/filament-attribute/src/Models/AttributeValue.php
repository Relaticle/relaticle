<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use ManukMinasyan\FilamentAttribute\Enums\AttributeType;

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
        AttributeType::TEXT->value => 'text_value',
        AttributeType::TEXTAREA->value => 'text_value',
        AttributeType::SELECT->value => 'integer_value',
        AttributeType::PRICE->value => 'float_value',
        AttributeType::MULTISELECT->value => 'json_value',
        AttributeType::TOGGLE->value => 'boolean_value',
        AttributeType::DATE->value => 'date_value',
        AttributeType::DATETIME->value => 'datetime_value',
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
