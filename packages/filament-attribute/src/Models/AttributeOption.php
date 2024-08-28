<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Models;

use ManukMinasyan\FilamentAttribute\Database\Factories\AttributeOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AttributeOption extends Model
{
    /** @use HasFactory<AttributeOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * @return BelongsTo<Attribute, AttributeOption>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
