<?php

declare(strict_types=1);

namespace Database\Factories\Relaticle\Chat\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Chat\Models\AiCreditBalance;

/**
 * @extends Factory<AiCreditBalance>
 */
final class AiCreditBalanceFactory extends Factory
{
    protected $model = AiCreditBalance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'credits_remaining' => fake()->numberBetween(0, 500),
            'credits_used' => fake()->numberBetween(0, 500),
            'period_starts_at' => now()->startOfMonth(),
            'period_ends_at' => now()->endOfMonth(),
        ];
    }
}
