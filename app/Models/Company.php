<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ManukMinasyan\FilamentCustomField\Models\Concerns\UsesCustomFields;
use ManukMinasyan\FilamentCustomField\Models\Contracts\HasCustomFields;

/**
 * @property string $name
 * @property string $address
 * @property string $country
 * @property string $phone
 */
final class Company extends Model implements HasCustomFields
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;
    use UsesCustomFields;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'country',
        'phone',
    ];

    public function getLogoAttribute(): ?string
    {
        return 'https://ui-avatars.com/api/?background=random&length=1&name=' . urlencode($this->name);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
