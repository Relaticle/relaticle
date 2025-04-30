<?php

declare(strict_types=1);

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Observers\CompanyObserver;
use App\Services\AvatarService;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $name
 * @property string $address
 * @property string $country
 * @property string $phone
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 */
#[ObservedBy(CompanyObserver::class)]
#[ApiResource]
final class Company extends Model implements HasCustomFields, HasMedia
{
    use HasCreator;

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'country',
        'phone',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
        ];
    }

    public function getLogoAttribute(): ?string
    {
        $logo = $this->getFirstMediaUrl('logo');

        return $logo === '' || $logo === '0' ? app(AvatarService::class)->generateAuto(name: $this->name) : $logo;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_owner_id');
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
