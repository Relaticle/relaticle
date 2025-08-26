<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Policies;

use App\Models\Note;
use App\Models\SystemAdministrator;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

final class NotePolicy
{
    public function viewAny(SystemAdministrator $admin): bool
    {
        return true; // System admins can view all notes across all tenants
    }

    public function view(SystemAdministrator $admin, Note $note): bool
    {
        return true; // System admins can view any specific note
    }

    public function create(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function update(SystemAdministrator $admin, Note $note): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function delete(SystemAdministrator $admin, Note $note): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function deleteAny(SystemAdministrator $admin): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function restore(SystemAdministrator $admin, Note $note): bool
    {
        return $admin->role === SystemAdministratorRole::SuperAdministrator;
    }

    public function forceDelete(SystemAdministrator $admin, Note $note): bool
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