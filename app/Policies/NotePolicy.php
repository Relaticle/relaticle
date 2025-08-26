<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Filament\Facades\Filament;

final class NotePolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public function deleteAny(): bool
    {
        return true;
    }

    public function restore(): bool
    {
        return true;
    }

    public function restoreAny(): bool
    {
        return true;
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}
