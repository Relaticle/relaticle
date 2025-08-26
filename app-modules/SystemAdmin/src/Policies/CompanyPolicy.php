<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\Company;
use App\Models\SystemAdministrator;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

final class CompanyPolicy
{
    public function viewAny(SystemAdministrator $admin): bool
    {
        return true; // System admins can view all companies across all tenants
    }

    public function view(SystemAdministrator $admin, Company $company): bool
    {
        return true; // System admins can view any specific company
    }

    public function create(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function update(SystemAdministrator $admin, Company $company): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function delete(SystemAdministrator $admin, Company $company): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function deleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restore(SystemAdministrator $admin, Company $company): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDelete(SystemAdministrator $admin, Company $company): bool
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