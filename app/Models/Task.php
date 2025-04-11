<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Spatie\EloquentSortable\SortableTrait;

final class Task extends Model implements HasCustomFields
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use SoftDeletes;
    use SortableTrait;
    use UsesCustomFields;

    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taskable');
    }

    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'taskable');
    }

    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'taskable');
    }
}
