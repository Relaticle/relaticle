<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PendingActionOperation;
use App\Enums\PendingActionStatus;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $team_id
 * @property string $user_id
 * @property string $conversation_id
 * @property string|null $message_id
 * @property string $action_class
 * @property PendingActionOperation $operation
 * @property string $entity_type
 * @property array<string, mixed> $action_data
 * @property array<string, mixed> $display_data
 * @property PendingActionStatus $status
 * @property Carbon $expires_at
 * @property Carbon|null $resolved_at
 * @property array<string, mixed>|null $result_data
 */
final class PendingAction extends Model
{
    use HasTeam;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'team_id',
        'user_id',
        'conversation_id',
        'message_id',
        'action_class',
        'operation',
        'entity_type',
        'action_data',
        'display_data',
        'status',
        'expires_at',
        'resolved_at',
        'result_data',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'operation' => PendingActionOperation::class,
            'status' => PendingActionStatus::class,
            'action_data' => 'array',
            'display_data' => 'array',
            'expires_at' => 'datetime',
            'resolved_at' => 'datetime',
            'result_data' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === PendingActionStatus::Pending;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @param  Builder<PendingAction>  $query
     * @return Builder<PendingAction>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PendingActionStatus::Pending);
    }

    /**
     * @param  Builder<PendingAction>  $query
     * @return Builder<PendingAction>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where('status', PendingActionStatus::Pending)
            ->where('expires_at', '<', now());
    }
}
