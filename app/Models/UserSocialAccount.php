<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserSocialAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
