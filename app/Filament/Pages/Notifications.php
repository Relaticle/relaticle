<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\AccountWidget;

class Notifications extends Page
{
    protected static ?string $navigationIcon = 'heroicon-m-bell';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = 2;

    public function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
        ];
    }
}
