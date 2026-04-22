<?php

declare(strict_types=1);

namespace App\Models\ActivityLog\Scopes;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where('team_id', $tenantId);
    }
}
