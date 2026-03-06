<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'category',
        'icon',
        'definition',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'definition' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getTable(): string
    {
        return config('workflow.table_prefix', '') . 'workflow_templates';
    }

    /**
     * Scope to only active templates.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
