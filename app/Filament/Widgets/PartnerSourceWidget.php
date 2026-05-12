<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PartnerSource;
use App\Models\Company;
use Filament\Widgets\ChartWidget;

final class PartnerSourceWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return 'Concentration by Partner Source';
    }

    public function getDescription(): string
    {
        return 'Share of portfolio concentration per acquisition channel.';
    }

    /** @return array<string, int|string> */
    public function getColumnSpan(): array
    {
        return ['default' => 'full', 'lg' => 1];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $rawData = Company::query()
            ->selectRaw('partner_source, SUM(COALESCE(concentration_percentage, 0)) AS total')
            ->whereNotNull('partner_source')
            ->groupBy('partner_source')
            ->pluck('total', 'partner_source');

        $colorMap = [
            PartnerSource::Direct->value => '#3b82f6',
            PartnerSource::ReferralPartner->value => '#10b981',
            PartnerSource::ChannelPartner->value => '#f59e0b',
            PartnerSource::Reseller->value => '#8b5cf6',
            PartnerSource::MarketingInbound->value => '#ef4444',
            PartnerSource::Event->value => '#6b7280',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($rawData as $source => $total) {
            $enum = PartnerSource::tryFrom((string) $source);
            $labels[] = $enum?->getLabel() ?? (string) $source;
            $data[] = round((float) $total, 2);
            $colors[] = $colorMap[$source] ?? '#94a3b8';
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                    'hoverOffset' => 6,
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
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
