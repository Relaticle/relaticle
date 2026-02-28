<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\Workflow\Enums\TriggerType;

class Workflow extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'canvas_data',
        'is_active',
        'last_triggered_at',
        'tenant_id',
        'creator_id',
    ];

    protected $casts = [
        'trigger_type' => TriggerType::class,
        'trigger_config' => 'array',
        'canvas_data' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflows';
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
}
