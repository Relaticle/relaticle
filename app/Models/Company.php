<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ManukMinasyan\FilamentAttribute\Models\Concerns\UsesCustomAttributes;
use ManukMinasyan\FilamentAttribute\Models\Contracts\HasCustomAttributes;

/**
 * @property string $name
 * @property string $address
 * @property string $country
 * @property string $phone
 */
final class Company extends Model implements HasCustomAttributes
{
    use UsesCustomAttributes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'country',
        'phone',
    ];

    protected static function boot(): void
    {
        parent::boot();
        self::bootUsesCustomAttributes();
    }
}
