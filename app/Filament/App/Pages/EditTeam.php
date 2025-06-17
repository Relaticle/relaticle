<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Override;
use Filament\Facades\Filament;
use Filament\Pages\Tenancy\EditTenantProfile;

final class EditTeam extends EditTenantProfile
{
    protected string $view = 'filament.pages.edit-team';

    protected static ?string $slug = 'team';

    protected static ?int $navigationSort = 2;

    #[Override]
    public static function getLabel(): string
    {
        return 'Team Settings';
    }

    #[Override]
    protected function getViewData(): array
    {
        return [
            'team' => Filament::getTenant(),
        ];
    }
}
