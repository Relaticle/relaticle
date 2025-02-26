<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
    ];

    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(People::class);
    }
}
