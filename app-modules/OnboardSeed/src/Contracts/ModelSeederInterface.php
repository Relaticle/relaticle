<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\Contracts;

use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

interface ModelSeederInterface
{
    /**
     * Run the model seed process
     *
     * @param  Team  $team  The team to create data for
     * @param  Authenticatable  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    public function seed(Team $team, Authenticatable $user, array $context = []): array;

    /**
     * Get custom fields for this model
     *
     * @return Collection<string, mixed>
     */
    public function customFields(): Collection;

    /**
     * Initialize the seeder with necessary dependencies
     *
     * @return $this
     */
    public function initialize(): self;
}
