<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\Workflow\Enums\NodeType;
use Relaticle\Workflow\Enums\TriggerType;
use Relaticle\Workflow\Enums\WorkflowStatus;
use Relaticle\Workflow\Models\Concerns\BelongsToTenant;

class Workflow extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'canvas_data',
        'canvas_version',
        'webhook_secret',
        'status',
        'published_at',
        'last_triggered_at',
        'tenant_id',
        'creator_id',
    ];

    protected $casts = [
        'trigger_type' => TriggerType::class,
        'trigger_config' => 'array',
        'canvas_data' => 'array',
        'canvas_version' => 'integer',
        'status' => WorkflowStatus::class,
        'published_at' => 'datetime',
        'last_triggered_at' => 'datetime',
    ];

    public function getIsActiveAttribute(): bool
    {
        return $this->status === WorkflowStatus::Live;
    }

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflows';
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(
            config('workflow.team_model', 'App\\Models\\Team'),
            'tenant_id',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('workflow.user_model', 'App\\Models\\User'),
            'creator_id',
        );
    }

    /**
     * @return HasMany<WorkflowNode, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class);
    }

    /**
     * @return HasMany<WorkflowEdge, $this>
     */
    public function edges(): HasMany
    {
        return $this->hasMany(WorkflowEdge::class);
    }

    /**
     * @return HasMany<WorkflowRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    /**
     * @return HasMany<WorkflowFavorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(WorkflowFavorite::class);
    }

    public function isFavoritedBy($user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function toggleFavorite($user): void
    {
        if (! $user) {
            return;
        }

        $existing = $this->favorites()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $this->favorites()->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
        }
    }

    public function canActivate(): bool
    {
        return empty($this->getActivationErrors());
    }

    /**
     * @return array<int, string>
     */
    public function getActivationErrors(): array
    {
        $errors = [];
        $nodes = $this->nodes()->get();

        if ($nodes->isEmpty()) {
            $errors[] = 'Workflow must have exactly one trigger node.';

            return $errors;
        }

        $triggerCount = $nodes->filter(fn ($node) => $node->type === NodeType::Trigger)->count();
        if ($triggerCount === 0) {
            $errors[] = 'Workflow must have exactly one trigger node.';
        } elseif ($triggerCount > 1) {
            $errors[] = 'Workflow must have exactly one trigger node.';
        }

        $hasAction = $nodes->contains(fn ($node) => in_array($node->type, [
            NodeType::Action,
            NodeType::Condition,
            NodeType::Delay,
            NodeType::Loop,
        ], true));
        if (! $hasAction) {
            $errors[] = 'Workflow must have at least one action or logic node.';
        }

        $unconfiguredActions = $nodes->filter(fn ($node) => $node->type === NodeType::Action && empty($node->action_type));
        if ($unconfiguredActions->isNotEmpty()) {
            $errors[] = 'All action nodes must have an action type configured.';
        }

        return $errors;
    }
}
