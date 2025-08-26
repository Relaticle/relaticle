<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SystemAdministrator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;

/**
 * @extends Factory<SystemAdministrator>
 */
final class SystemAdministratorFactory extends Factory
{
    protected $model = SystemAdministrator::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => SystemAdministratorRole::SuperAdministrator,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
