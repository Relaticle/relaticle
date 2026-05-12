<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Support\CountryFlag;
use Filament\Widgets\ChartWidget;

final class GeographyDistributionWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return 'Accounts by Geography';
    }

    public function getDescription(): string
    {
        return 'Top 10 markets by number of accounts.';
    }

    /** @return array<string, int|string> */
    public function getColumnSpan(): array
    {
        return ['default' => 'full', 'lg' => 2];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $rawData = Company::query()
            ->selectRaw('geography, COUNT(*) AS cnt')
            ->whereNotNull('geography')
            ->groupBy('geography')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt', 'geography');

        $labels = [];
        $counts = [];

        foreach ($rawData as $code => $count) {
            $labels[] = CountryFlag::emoji((string) $code).' '.$code;
            $counts[] = (int) $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Accounts',
                    'data' => $counts,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.75)',
                    'borderColor' => '#6366f1',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
