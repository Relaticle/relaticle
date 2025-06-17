<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected ?string $heading = 'Dashboard';

    protected ?string $subheading = 'Welcome to Relaticle Admin | See your stats and manage your content.';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeaderActions(): array
    {
        return [
            Action::make('view-site')
                ->label('View Website')
                ->url('/')
                ->icon('heroicon-o-globe-alt')
                ->color('gray')
                ->openUrlInNewTab(),
        ];
    }
}
