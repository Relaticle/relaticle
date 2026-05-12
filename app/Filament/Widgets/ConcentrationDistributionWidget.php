<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\ChartWidget;

final class ConcentrationDistributionWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return 'Concentration Distribution';
    }

    public function getDescription(): string
    {
        return 'Number of accounts per risk bucket.';
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
        $bucketOrder = ['0–10%', '10–20%', '20–30%', '30–40%', '40%+'];

        $rawCounts = Company::query()
            ->selectRaw("
                CASE
                    WHEN concentration_percentage IS NULL OR concentration_percentage < 10 THEN '0–10%'
                    WHEN concentration_percentage < 20 THEN '10–20%'
                    WHEN concentration_percentage < 30 THEN '20–30%'
                    WHEN concentration_percentage < 40 THEN '30–40%'
                    ELSE '40%+'
                END AS bucket,
                COUNT(*) AS cnt
            ")
            ->groupByRaw('1')
            ->pluck('cnt', 'bucket');

        $counts = array_map(
            fn (string $bucket): int => (int) ($rawCounts[$bucket] ?? 0),
            array_combine($bucketOrder, $bucketOrder),
        );

        return [
            'datasets' => [
                [
                    'label' => 'Accounts',
                    'data' => array_values($counts),
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',   // emerald — low risk
                        'rgba(20, 184, 166, 0.8)',   // teal
                        'rgba(245, 158, 11, 0.8)',   // amber
                        'rgba(249, 115, 22, 0.8)',   // orange
                        'rgba(244, 63, 94, 0.8)',    // rose — high risk
                    ],
                    'borderColor' => [
                        '#10b981', '#14b8a6', '#f59e0b', '#f97316', '#f43f5e',
                    ],
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $bucketOrder,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
