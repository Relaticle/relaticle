<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PartnerSource;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds ~50 companies with realistic portfolio metadata distribution.
 *
 * Concentration distribution:
 *   ~60% in 0–10%  (low risk, healthy portfolio)
 *   ~20% in 10–30% (medium risk)
 *   ~10% in 30–40% (elevated risk)
 *   ~10% above 40% (high risk — makes concentration demos meaningful)
 *
 * Geography: 10 countries covering major B2B SaaS markets.
 * Recurring: ~60% of accounts.
 */
final class PortfolioSeeder extends Seeder
{
    /** @var list<string> */
    private const array GEOGRAPHIES = ['US', 'GB', 'CA', 'DE', 'FR', 'AU', 'JP', 'BR', 'NL', 'SG'];

    public function run(User $user, Team $team): void
    {
        $concentrationBuckets = array_merge(
            array_fill(0, 30, 'low'),       // 60% → 0–10
            array_fill(0, 10, 'medium'),    // 20% → 10–30
            array_fill(0, 5, 'elevated'),   // 10% → 30–40
            array_fill(0, 5, 'high'),       // 10% → 40–80
        );

        $partnerSources = PartnerSource::cases();
        $geographies = self::GEOGRAPHIES;

        for ($i = 0; $i < 50; $i++) {
            $bucket = $concentrationBuckets[$i] ?? 'low';

            $concentration = match ($bucket) {
                'low' => round(fake()->randomFloat(2, 0.5, 9.99), 2),
                'medium' => round(fake()->randomFloat(2, 10.0, 29.99), 2),
                'elevated' => round(fake()->randomFloat(2, 30.0, 39.99), 2),
                default => round(fake()->randomFloat(2, 40.0, 75.0), 2),
            };

            Company::factory()
                ->for($team, 'team')
                ->for($user, 'creator')
                ->create([
                    'partner_source' => $partnerSources[$i % count($partnerSources)]->value,
                    'geography' => $geographies[$i % count($geographies)],
                    'concentration_percentage' => $concentration,
                    'is_recurring' => $i % 10 < 6, // 60% recurring
                ]);
        }
    }
}
