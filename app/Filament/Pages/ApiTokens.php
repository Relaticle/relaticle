<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Override;

final class ApiTokens extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected string $view = 'filament.pages.api-tokens';

    protected static ?string $navigationLabel = 'API Tokens';

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }
}
