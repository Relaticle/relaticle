<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\SystemAdministrator;
use App\Models\User;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

final class UserPolicy
{
    public function viewAny(SystemAdministrator $admin): bool
    {
        return true; // System admins can view all users across all tenants
    }

    public function view(SystemAdministrator $admin, User $user): bool
    {
        return true; // System admins can view any user
    }

    public function create(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function update(SystemAdministrator $admin, User $user): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function delete(SystemAdministrator $admin, User $user): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function deleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restore(SystemAdministrator $admin, User $user): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDelete(SystemAdministrator $admin, User $user): bool
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