<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Relaticle\Workflow\Enums\StepStatus;

class WorkflowRunStep extends Model
{
    use HasUlids;

    protected $fillable = [
        'workflow_run_id',
        'workflow_node_id',
        'status',
        'input_data',
        'output_data',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => StepStatus::class,
        'input_data' => 'array',
        'output_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflow_run_steps';
    }

    /**
     * @return BelongsTo<WorkflowRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class, 'workflow_run_id');
    }

    /**
     * @return BelongsTo<WorkflowNode, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'workflow_node_id');
    }
}
