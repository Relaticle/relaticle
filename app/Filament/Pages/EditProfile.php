<?php

namespace App\Filament\Pages;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use ManukMinasyan\FilamentCustomField\Filament\Resources\CustomFieldResource;
use ManukMinasyan\FilamentCustomField\Services\EntityTypeOptionsService;

class EditProfile extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.edit-profile';

    protected static ?string $navigationLabel = 'Profile';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
