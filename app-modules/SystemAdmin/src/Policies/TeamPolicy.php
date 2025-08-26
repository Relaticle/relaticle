<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\SystemAdministrator;
use App\Models\Team;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

final class TeamPolicy
{
    public function viewAny(SystemAdministrator $admin): bool
    {
        return true; // System admins can view all teams
    }

    public function view(SystemAdministrator $admin, Team $team): bool
    {
        return true; // System admins can view any team
    }

    public function create(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function update(SystemAdministrator $admin, Team $team): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function delete(SystemAdministrator $admin, Team $team): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function deleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restore(SystemAdministrator $admin, Team $team): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDelete(SystemAdministrator $admin, Team $team): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDeleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restoreAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }
}