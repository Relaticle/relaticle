<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowFavorite extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'workflow_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflow_favorites';
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
