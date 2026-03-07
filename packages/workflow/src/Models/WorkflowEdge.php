<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowEdge extends Model
{
    use HasUlids;

    protected $fillable = [
        'workflow_id',
        'edge_id',
        'source_node_id',
        'target_node_id',
        'condition_label',
        'condition_config',
    ];

    protected $casts = [
        'condition_config' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $edge) {
            if (empty($edge->edge_id)) {
                $edge->edge_id = 'e-' . Str::ulid()->toBase32();
            }
        });
    }

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflow_edges';
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<WorkflowNode, $this>
     */
    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'source_node_id');
    }

    /**
     * @return BelongsTo<WorkflowNode, $this>
     */
    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'target_node_id');
    }
}
