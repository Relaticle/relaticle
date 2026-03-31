<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Relaticle\ActivityLog\Contracts\TenantResolver;

final class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = resolve(TenantResolver::class)->resolve();

        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where('team_id', $tenantId);
    }
}
