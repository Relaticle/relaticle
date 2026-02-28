<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Relaticle\Workflow\WorkflowManager;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $manager = app(WorkflowManager::class);
        $config = $manager->getTenancyConfig();

        if ($config === null) {
            return;
        }

        $tenantId = ($config['resolver'])();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn($config['scopeColumn']), $tenantId);
        }
    }
}
