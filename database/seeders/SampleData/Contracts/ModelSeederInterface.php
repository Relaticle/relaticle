<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\Contracts;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

interface ModelSeederInterface
{
    /**
     * Run the model seed process
     * 
     * @param Team $team The team to create data for
     * @param User $user The user creating the data
     * @param array<string, mixed> $context Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    public function seed(Team $team, User $user, array $context = []): array;
    
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