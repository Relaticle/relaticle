<?php

namespace App\Models;

use Database\Factories\PeopleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use ManukMinasyan\FilamentCustomField\Models\Concerns\UsesCustomFields;
use ManukMinasyan\FilamentCustomField\Models\Contracts\HasCustomFields;

class People extends Model implements HasCustomFields
{
    /** @use HasFactory<PeopleFactory> */
    use HasFactory;
    use UsesCustomFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }
}
