<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Relaticle\Workflow\Enums\NodeType;

class WorkflowNode extends Model
{
    use HasUlids;

    protected $fillable = [
        'workflow_id',
        'node_id',
        'type',
        'action_type',
        'config',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'type' => NodeType::class,
        'config' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflow_nodes';
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
