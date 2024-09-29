<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next)
    {
        User::addGlobalScope(
            fn(Builder $query) => $query
                ->whereHas('teams', fn(Builder $query) => $query->where('teams.id', Filament::getTenant()->id))
                ->orWhereHas('ownedTeams', fn(Builder $query) => $query->where('teams.id', Filament::getTenant()->id))
        );

        return $next($request);
    }
}