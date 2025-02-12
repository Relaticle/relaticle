<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

class Task extends Model implements HasCustomFields
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;
    use SoftDeletes;
    use UsesCustomFields;

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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(People::class);
    }
}
