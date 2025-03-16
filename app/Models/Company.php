<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AvatarService;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

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
        return app(AvatarService::class)->generateAuto(name: $this->name);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function notes(): MorphToMany
    {
        return $this->morphToMany(Note::class, 'noteable');
    }
}
