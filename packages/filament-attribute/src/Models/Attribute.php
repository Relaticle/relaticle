<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Models;

use ManukMinasyan\FilamentAttribute\Enums\AttributeTypeEnum;
use ManukMinasyan\FilamentAttribute\Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AttributeTypeEnum $type
 * @property Model $entity_type
 * @property Model|null $lookup_type
 */
final class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'entity_type',
        'type',
        'lookup_type',
        'name',
        'code'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttributeTypeEnum::class,
//            'entity_type' => Model::class,
//            'lookup_type' => AttributeLookupTypeEnum::class,
        ];
    }

    /**
     * @param  Builder<Attribute>  $builder
     * @return Builder<Attribute>
     *
     * @noinspection PhpUnused
     */
    public function scopeForType(Builder $builder, AttributeTypeEnum $type): Builder
    {
        return $builder->where('type', $type);
    }

    /**
     * @param  Builder<Attribute>  $builder
     * @return Builder<Attribute>
     *
     * @noinspection PhpUnused
     */
    public function scopeForEntity(Builder $builder, string $entity): Builder
    {
        return $builder->where('entity_type', $entity);
    }

    /**
     * @return HasMany<AttributeValue>
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * @return HasMany<AttributeOption>
     */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}
