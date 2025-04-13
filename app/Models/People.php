<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\PeopleObserver;
use App\Services\AvatarService;
use Database\Factories\PeopleFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

/**
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
#[ObservedBy(PeopleObserver::class)]
final class People extends Model implements HasCustomFields
{
    /** @use HasFactory<PeopleFactory> */
    use HasFactory;

    use SoftDeletes;
    use UsesCustomFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    public function getAvatarAttribute(): ?string
    {
        return app(AvatarService::class)->generateAuto(name: $this->name, initialCount: 1);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
