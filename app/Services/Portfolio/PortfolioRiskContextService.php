<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use App\Enums\RiskBand;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class PortfolioRiskContextService
{
    /**
     * Build concentration report data for the entire portfolio.
     *
     * @return array<string, mixed>
     */
    public function concentrationReport(): array
    {
        /** @var Collection<int, Company> $all */
        $all = Company::query()
            ->whereNotNull('concentration_percentage')
            ->orderByDesc('concentration_percentage')
            ->get(['id', 'name', 'concentration_percentage', 'partner_source', 'geography', 'is_recurring']);

        $total = Company::query()->count();
        $withConcentration = $all->count();
        $totalConcentration = (float) $all->sum('concentration_percentage');
        $avg = $withConcentration > 0 ? $totalConcentration / $withConcentration : 0.0;
        $max = $withConcentration > 0 ? (float) $all->first()?->concentration_percentage : 0.0;

        $hhi = $all->sum(
            fn (Company $c): float => (((float) $c->concentration_percentage) / 100) ** 2
        );

        $byRiskBand = ['low' => 0, 'medium' => 0, 'high' => 0];
        foreach ($all as $company) {
            $band = $company->portfolio->riskBand()->value;
            $byRiskBand[$band]++;
        }

        /** @var array<string, array{count: int, total_concentration: float}> $bySource */
        $bySource = [];
        foreach ($all as $company) {
            if ($company->partner_source === null) {
                continue;
            }
            $key = $company->partner_source->value;
            $bySource[$key] ??= ['count' => 0, 'total_concentration' => 0.0];
            $bySource[$key]['count']++;
            $bySource[$key]['total_concentration'] += (float) $company->concentration_percentage;
        }

        $topRisks = $all->take(10)->map(fn (Company $c): array => [
            'id' => $c->getKey(),
            'name' => $c->name,
            'concentration_percentage' => round((float) $c->concentration_percentage, 2),
            'risk_band' => $c->portfolio->riskBand()->value,
            'partner_source' => $c->partner_source?->value,
            'geography' => $c->geography,
        ])->values()->all();

        return [
            'summary' => [
                'total_accounts' => $total,
                'accounts_with_concentration' => $withConcentration,
                'total_concentration' => round($totalConcentration, 2),
                'average_concentration' => round($avg, 2),
                'max_concentration' => round($max, 2),
                'hhi' => round($hhi, 6),
                'hhi_interpretation' => $this->hhiLabel($hhi),
            ],
            'by_risk_band' => $byRiskBand,
            'by_partner_source' => $bySource,
            'top_risks' => $topRisks,
        ];
    }

    /**
     * Build structured risk context for a single company — suitable for LLM consumption.
     *
     * @return array<string, mixed>
     */
    public function riskContext(Company $company): array
    {
        $concentration = $company->concentration_percentage !== null
            ? (float) $company->concentration_percentage
            : null;

        $portfolioStats = DB::table('companies')
            ->whereNotNull('concentration_percentage')
            ->selectRaw('COUNT(*) as total, AVG(concentration_percentage) as avg_conc, MAX(concentration_percentage) as max_conc')
            ->first();

        $total = (int) ($portfolioStats->total ?? 0);
        $avgConc = round((float) ($portfolioStats->avg_conc ?? 0), 2);

        $rank = null;
        $percentile = null;
        $aboveCount = 0;

        if ($concentration !== null && $total > 0) {
            $aboveCount = Company::query()
                ->whereNotNull('concentration_percentage')
                ->where('concentration_percentage', '>', $concentration)
                ->count();

            $rank = $aboveCount + 1;
            $percentile = (int) round(100 - ($aboveCount / $total * 100));
        }

        $sameSourceCount = $company->partner_source !== null
            ? Company::query()->where('partner_source', $company->partner_source)->count()
            : null;

        $sameGeoCount = $company->geography !== null
            ? Company::query()->where('geography', $company->geography)->count()
            : null;

        $sameRiskBandCount = Company::query()
            ->whereNotNull('concentration_percentage')
            ->when(
                $company->portfolio->riskBand() === RiskBand::Low,
                fn (Builder $q) => $q->where('concentration_percentage', '<', 10)
            )
            ->when(
                $company->portfolio->riskBand() === RiskBand::Medium,
                fn (Builder $q) => $q->whereBetween('concentration_percentage', [10, 29.99])
            )
            ->when(
                $company->portfolio->riskBand() === RiskBand::High,
                fn (Builder $q) => $q->where('concentration_percentage', '>=', 30)
            )
            ->count();

        return [
            'company' => [
                'id' => $company->getKey(),
                'name' => $company->name,
                'concentration_percentage' => $concentration,
                'risk_band' => $company->portfolio->riskBand()->value,
                'risk_band_label' => $company->portfolio->riskBand()->getLabel(),
                'partner_source' => $company->partner_source?->value,
                'partner_source_label' => $company->partner_source?->getLabel(),
                'geography' => $company->geography,
                'is_recurring' => $company->is_recurring,
            ],
            'portfolio_context' => [
                'portfolio_average_concentration' => $avgConc,
                'total_accounts_with_concentration' => $total,
                'concentration_rank' => $rank,
                'concentration_percentile' => $percentile,
                'accounts_above_this_concentration' => $aboveCount,
                'accounts_in_same_risk_band' => $sameRiskBandCount,
                'accounts_with_same_partner_source' => $sameSourceCount,
                'accounts_in_same_geography' => $sameGeoCount,
            ],
            'narrative_prompt' => $this->buildNarrativePrompt($company, $concentration, $avgConc, $rank, $total),
        ];
    }

    /**
     * Compute the what-if impact of changing a company's concentration.
     *
     * @return array<string, mixed>
     */
    public function whatIf(Company $company, float $newConcentration): array
    {
        $currentConcentration = $company->concentration_percentage !== null
            ? (float) $company->concentration_percentage
            : 0.0;

        $currentBand = $company->portfolio->riskBand();

        // Determine new risk band based on new concentration
        $newBand = match (true) {
            $newConcentration >= 30 => RiskBand::High,
            $newConcentration >= 10 => RiskBand::Medium,
            default => RiskBand::Low,
        };

        $portfolioStats = DB::table('companies')
            ->whereNotNull('concentration_percentage')
            ->selectRaw('COUNT(*) as total, AVG(concentration_percentage) as avg_conc, SUM(POW(concentration_percentage / 100, 2)) as hhi')
            ->first();

        $total = (int) ($portfolioStats->total ?? 1);
        $currentAvg = (float) ($portfolioStats->avg_conc ?? 0);
        $currentHhi = (float) ($portfolioStats->hhi ?? 0);

        // Remove old contribution, add new contribution
        $avgDelta = ($newConcentration - $currentConcentration) / $total;
        $projectedAvg = $currentAvg + $avgDelta;

        $oldHhiContribution = ($currentConcentration / 100) ** 2;
        $newHhiContribution = ($newConcentration / 100) ** 2;
        $projectedHhi = $currentHhi - $oldHhiContribution + $newHhiContribution;

        $bandChanged = $currentBand !== $newBand;

        return [
            'current' => [
                'concentration_percentage' => round($currentConcentration, 2),
                'risk_band' => $currentBand->value,
                'risk_band_label' => $currentBand->getLabel(),
                'portfolio_average_concentration' => round($currentAvg, 2),
                'portfolio_hhi' => round($currentHhi, 6),
            ],
            'projected' => [
                'concentration_percentage' => round($newConcentration, 2),
                'risk_band' => $newBand->value,
                'risk_band_label' => $newBand->getLabel(),
                'portfolio_average_concentration' => round($projectedAvg, 2),
                'portfolio_hhi' => round($projectedHhi, 6),
            ],
            'delta' => [
                'concentration_change' => round($newConcentration - $currentConcentration, 2),
                'risk_band_change' => $bandChanged
                    ? $currentBand->getLabel().' → '.$newBand->getLabel()
                    : 'No change ('.$currentBand->getLabel().')',
                'portfolio_avg_change' => round($avgDelta, 2),
                'portfolio_hhi_change' => round($projectedHhi - $currentHhi, 6),
                'risk_band_changed' => $bandChanged,
            ],
            'interpretation' => $this->buildWhatIfInterpretation($company, $currentConcentration, $newConcentration, $currentBand, $newBand),
        ];
    }

    private function hhiLabel(float $hhi): string
    {
        return match (true) {
            $hhi < 0.01 => 'Highly diversified',
            $hhi < 0.15 => 'Moderately diversified',
            $hhi < 0.25 => 'Moderately concentrated',
            default => 'Highly concentrated',
        };
    }

    private function buildNarrativePrompt(
        Company $company,
        ?float $concentration,
        float $avgConc,
        ?int $rank,
        int $total
    ): string {
        $name = $company->name;
        $band = $company->portfolio->riskBand()->getLabel();
        $source = $company->partner_source?->getLabel() ?? 'unknown source';
        $geo = $company->geography ?? 'unknown geography';
        $concText = $concentration !== null ? number_format($concentration, 1).'%' : 'no concentration data';
        $rankText = $rank !== null ? "ranked #{$rank} of {$total}" : '';

        return "Explain the portfolio risk for account '{$name}'. "
            ."It has a concentration of {$concText} ({$band}), {$rankText}. "
            .'Portfolio average is '.number_format($avgConc, 1).'%. '
            ."Acquired via {$source}, based in {$geo}. "
            .'Provide a concise risk assessment and recommended actions for the account manager.';
    }

    private function buildWhatIfInterpretation(
        Company $company,
        float $current,
        float $projected,
        RiskBand $currentBand,
        RiskBand $newBand
    ): string {
        $direction = $projected > $current ? 'increasing' : 'reducing';
        $change = abs($projected - $current);
        $bandNote = $currentBand !== $newBand
            ? " This would move the account from {$currentBand->getLabel()} to {$newBand->getLabel()}."
            : " The risk band would remain {$currentBand->getLabel()}.";

        return "{$direction} {$company->name}'s concentration by ".number_format($change, 1).'pp '
            .'(from '.number_format($current, 1).'% to '.number_format($projected, 1)."%).{$bandNote}";
    }
}
