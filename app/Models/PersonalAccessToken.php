<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'team_id',
    ];

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
