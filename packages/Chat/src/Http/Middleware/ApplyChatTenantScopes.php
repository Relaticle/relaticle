<?php

declare(strict_types=1);

namespace Relaticle\Chat\Http\Middleware;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Scopes\TeamScope;
use App\Models\Task;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApplyChatTenantScopes
{
    public function handle(Request $request, Closure $next): Response
    {
        Company::addGlobalScope(new TeamScope);
        People::addGlobalScope(new TeamScope);
        Opportunity::addGlobalScope(new TeamScope);
        Task::addGlobalScope(new TeamScope);
        Note::addGlobalScope(new TeamScope);

        $user = $request->user();

        if ($user instanceof User && $user->currentTeam !== null) {
            Filament::setTenant($user->currentTeam);
        }

        return $next($request);
    }
}
