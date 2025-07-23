<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\User;
use Filament\Pages\Tenancy\EditTenantProfile;

final class EditTeam extends EditTenantProfile
{
    protected string $view = 'filament.pages.edit-team';

    protected static ?string $slug = 'team';

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return 'Team Settings';
    }

    public function mount(): void
    {
        parent::mount();

        // Load owner without global scopes since a team owner should always be accessible
        if ($this->tenant && $this->tenant->user_id) {
            $this->tenant->setRelation('owner', User::withoutGlobalScopes()->find($this->tenant->user_id));
        }
    }
}
