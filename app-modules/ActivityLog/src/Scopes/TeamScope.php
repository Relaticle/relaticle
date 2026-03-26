<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Scopes;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            $builder->where('team_id', $tenant->getKey());
        }
    }
}
