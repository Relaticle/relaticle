<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\AccountWidget;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-m-cog-6-tooth';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = 1;

    public function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
        ];
    }
}
