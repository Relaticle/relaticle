<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\Opportunity;
use App\Models\SystemAdministrator;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

final class OpportunityPolicy
{
    public function viewAny(SystemAdministrator $admin): bool
    {
        return true; // System admins can view all opportunities across all tenants
    }

    public function view(SystemAdministrator $admin, Opportunity $opportunity): bool
    {
        return true; // System admins can view any specific opportunity
    }

    public function create(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function update(SystemAdministrator $admin, Opportunity $opportunity): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function delete(SystemAdministrator $admin, Opportunity $opportunity): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function deleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restore(SystemAdministrator $admin, Opportunity $opportunity): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDelete(SystemAdministrator $admin, Opportunity $opportunity): bool
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

    public function reorder(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }
}