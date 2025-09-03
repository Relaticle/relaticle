<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

final class EditProfile extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected string $view = 'filament.pages.edit-profile';

    protected static ?string $navigationLabel = 'Profile';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }
}
