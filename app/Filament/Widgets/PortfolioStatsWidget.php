<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PortfolioStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $total = Company::query()->count();
        $recurring = Company::query()->where('is_recurring', true)->count();
        $recurringPct = $total > 0 ? round($recurring / $total * 100, 1) : 0.0;

        $maxConcentration = (float) (Company::query()->max('concentration_percentage') ?? 0);
        $highRiskCount = Company::query()
            ->whereNotNull('concentration_percentage')
            ->where('concentration_percentage', '>=', 30)
            ->count();

        return [
            Stat::make('Total Accounts', number_format($total))
                ->description('Active companies in portfolio')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),

            Stat::make('Recurring Revenue Share', $recurringPct.'%')
                ->description("{$recurring} of {$total} accounts are recurring")
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($recurringPct >= 50 ? 'success' : 'warning'),

            Stat::make('Top Concentration', number_format($maxConcentration, 1).'%')
                ->description('Highest single-account concentration')
                ->descriptionIcon($maxConcentration >= 30 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color(match (true) {
                    $maxConcentration < 10 => 'success',
                    $maxConcentration < 30 => 'warning',
                    default => 'danger',
                }),

            Stat::make('High-Risk Accounts', (string) $highRiskCount)
                ->description('Accounts with ≥ 30% concentration')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($highRiskCount === 0 ? 'success' : 'danger'),
        ];
    }
}
