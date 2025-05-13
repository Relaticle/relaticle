<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Observers\OpportunityObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 */
#[ObservedBy(OpportunityObserver::class)]
final class Opportunity extends Model implements HasCustomFields
{
    use HasCreator;
    use HasFactory;
    use SoftDeletes;
    use SortableTrait;
    use UsesCustomFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(People::class);
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
