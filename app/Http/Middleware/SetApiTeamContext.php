<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Scopes\TeamScope;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetApiTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        $team = $this->resolveTeam($request, $user);

        if (! $team) {
            return response()->json(['message' => 'No team found.'], 403);
        }

        if (! $user->belongsToTeam($team)) {
            return response()->json(['message' => 'You do not belong to this team.'], 403);
        }

        $user->switchTeam($team);

        auth()->guard('web')->setUser($user);
        auth()->shouldUse('web');

        $this->applyTenantScopes($team);

        return $next($request);
    }

    private function resolveTeam(Request $request, User $user): ?Team
    {
        $teamId = $request->header('X-Team-Id');

        if ($teamId) {
            return Team::query()->firstWhere('id', $teamId);
        }

        // @phpstan-ignore return.type (Jetstream's currentTeam is typed as Model|null)
        return $user->currentTeam;
    }

    private function applyTenantScopes(Team $team): void
    {
        $tenantId = $team->getKey();

        User::addGlobalScope(
            'tenant',
            fn (Builder $query) => $query
                ->whereHas('teams', fn (Builder $query) => $query->where('teams.id', $tenantId))
                ->orWhereHas('ownedTeams', fn (Builder $query) => $query->where('teams.id', $tenantId))
        );

        Company::addGlobalScope(new TeamScope);
        People::addGlobalScope(new TeamScope);
        Opportunity::addGlobalScope(new TeamScope);
        Task::addGlobalScope(new TeamScope);
        Note::addGlobalScope(new TeamScope);
    }
}
