<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Models\Concerns;

use Relaticle\Workflow\Models\Scopes\TenantScope;
use Relaticle\Workflow\WorkflowManager;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $manager = app(WorkflowManager::class);
            $config = $manager->getTenancyConfig();

            if ($config !== null && empty($model->{$config['scopeColumn']})) {
                $tenantId = ($config['resolver'])();
                if ($tenantId !== null) {
                    $model->{$config['scopeColumn']} = $tenantId;
                }
            }
        });
    }
}
