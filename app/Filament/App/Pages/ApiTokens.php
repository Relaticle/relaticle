<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

final class ApiTokens extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.pages.api-tokens';

    protected static ?string $navigationLabel = 'API Tokens';

    #[\Override]
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }
}
