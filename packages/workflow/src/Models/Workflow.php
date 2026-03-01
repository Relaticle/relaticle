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
            $errors[] = 'Workflow must have at least one trigger node.';

            return $errors;
        }

        $hasTrigger = $nodes->contains(fn ($node) => $node->type === NodeType::Trigger);
        if (! $hasTrigger) {
            $errors[] = 'Workflow must have at least one trigger node.';
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
