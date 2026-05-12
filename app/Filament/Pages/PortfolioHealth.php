<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ConcentrationDistributionWidget;
use App\Filament\Widgets\ConcentrationRiskTableWidget;
use App\Filament\Widgets\GeographyDistributionWidget;
use App\Filament\Widgets\PartnerSourceWidget;
use App\Filament\Widgets\PortfolioStatsWidget;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use UnitEnum;

final class PortfolioHealth extends Dashboard
{
    protected static string $routePath = 'portfolio-health';

    protected static ?string $navigationLabel = 'Portfolio Health';

    protected static ?string $title = 'Portfolio Health';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 10;

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            PortfolioStatsWidget::class,
            ConcentrationDistributionWidget::class,
            PartnerSourceWidget::class,
            GeographyDistributionWidget::class,
            ConcentrationRiskTableWidget::class,
        ];
    }

    /** @return array<string, int|null> */
    public function getColumns(): array
    {
        return ['default' => 1, 'lg' => 3];
    }
}
