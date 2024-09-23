<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Tenancy\EditTenantProfile;
use Relaticle\CustomFields\Filament\Resources\CustomFieldResource;
use Relaticle\CustomFields\Services\EntityTypeOptionsService;

class EditTeam extends EditTenantProfile
{
    protected static string $view = 'filament.pages.edit-team';

    protected static ?string $slug = 'team';

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return 'Team Settings';
    }

    protected function getViewData(): array
    {
        return [
            'team' => Filament::getTenant(),
        ];
    }
}
