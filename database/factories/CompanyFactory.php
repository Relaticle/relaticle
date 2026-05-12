<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PartnerSource;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
final class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Weighted concentration: 60% low (<10), 20% medium (10–30), 10% elevated (30–40), 10% high (>40)
        $concentrationWeight = $this->faker->numberBetween(1, 10);
        $concentration = match (true) {
            $concentrationWeight <= 6 => $this->faker->randomFloat(2, 0.5, 9.99),
            $concentrationWeight <= 8 => $this->faker->randomFloat(2, 10.0, 29.99),
            $concentrationWeight === 9 => $this->faker->randomFloat(2, 30.0, 39.99),
            default => $this->faker->randomFloat(2, 40.0, 75.0),
        };

        return [
            'name' => $this->faker->company(),
            'account_owner_id' => User::factory(),
            'team_id' => Team::factory(),
            'partner_source' => $this->faker->randomElement(PartnerSource::cases())->value,
            'geography' => $this->faker->randomElement(['US', 'GB', 'CA', 'DE', 'FR', 'AU', 'JP', 'BR', 'NL', 'SG']),
            'concentration_percentage' => round($concentration, 2),
            'is_recurring' => $this->faker->boolean(60),
        ];
    }

    /** @phpstan-return static */
    public function configure(): static
    {
        $factory = $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);

        if (config('scribe.generating')) {
            return $factory->state([
                'account_owner_id' => (string) Str::ulid(),
                'team_id' => (string) Str::ulid(),
            ]);
        }

        return $factory;
    }
}
