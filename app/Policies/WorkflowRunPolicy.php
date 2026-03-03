<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Relaticle\Workflow\Models\WorkflowRun;

final readonly class WorkflowRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, WorkflowRun $workflowRun): bool
    {
        return $user->currentTeam !== null;
    }
}
