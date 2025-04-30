<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\CreationSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCreator
{
    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Determine if the record was created by the system.
     */
    public function isSystemCreated(): bool
    {
        return $this->creation_source === CreationSource::SYSTEM;
    }

    /**
     * Get the formatted name of who created this record.
     */
    public function createdBy(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->creation_source === CreationSource::SYSTEM ?
                '⊙ System' :
                $this->creator?->name ?? 'Unknown',
        );
    }
}
