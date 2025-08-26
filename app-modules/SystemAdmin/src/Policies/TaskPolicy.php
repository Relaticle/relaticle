<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\SystemAdministrator;
use App\Models\Task;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

final class TaskPolicy
{
    public function viewAny(SystemAdministrator $admin): bool
    {
        return true; // System admins can view all tasks across all tenants
    }

    public function view(SystemAdministrator $admin, Task $task): bool
    {
        return true; // System admins can view any specific task
    }

    public function create(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function update(SystemAdministrator $admin, Task $task): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function delete(SystemAdministrator $admin, Task $task): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function deleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restore(SystemAdministrator $admin, Task $task): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDelete(SystemAdministrator $admin, Task $task): bool
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