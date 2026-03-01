<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\Concerns\BelongsToTenant;

class WorkflowRun extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $fillable = [
        'workflow_id',
        'tenant_id',
        'trigger_record_type',
        'trigger_record_id',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'context_data',
    ];

    protected $casts = [
        'status' => WorkflowRunStatus::class,
        'context_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflow_runs';
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(
            config('workflow.team_model', 'App\\Models\\Team'),
            'tenant_id',
        );
    }

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return HasMany<WorkflowRunStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowRunStep::class);
    }
}
