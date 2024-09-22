<?php

namespace App\Filament\Pages;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use ManukMinasyan\FilamentCustomField\Filament\Resources\CustomFieldResource;
use ManukMinasyan\FilamentCustomField\Services\EntityTypeOptionsService;

class SettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;


    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Profile'),
            NavigationItem::make('Workspace'),
            NavigationItem::make('Data Model')
        ];
    }
}
