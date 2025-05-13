<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Relaticle\OnboardSeed\Contracts\ModelSeederInterface;
use Relaticle\OnboardSeed\ModelSeeders\CompanySeeder;
use Relaticle\OnboardSeed\ModelSeeders\NoteSeeder;
use Relaticle\OnboardSeed\ModelSeeders\OpportunitySeeder;
use Relaticle\OnboardSeed\ModelSeeders\PeopleSeeder;
use Relaticle\OnboardSeed\ModelSeeders\TaskSeeder;

final class OnboardSeedManager
{
    /**
     * The ordered sequence of model seeders to run
     *
     * @var array<class-string<ModelSeederInterface>>
     */
    private array $entitySeederSequence = [
        CompanySeeder::class,
        PeopleSeeder::class,
        OpportunitySeeder::class,
        TaskSeeder::class,
        NoteSeeder::class,
    ];

    /**
     * List of initialized seeders
     *
     * @var array<string, ModelSeederInterface>
     */
    private array $seeders = [];

    /**
     * Generate demo data for a user's team
     *
     * @param  Authenticatable  $user  The user to create demo data for
     * @return bool Whether the seeding was successful
     */
    public function generateFor(Authenticatable $user): bool
    {
        /** @var User $user */
        $team = $user->currentTeam;

        if (! $team) {
            return false;
        }

        try {
            $this->initializeSeeders();

            $seedingContext = [];

            // Run seeders in sequence
            foreach ($this->seeders as $seeder) {
                $result = $seeder->seed($team, $user, $seedingContext);

                // Merge new context data
                $seedingContext = array_merge($seedingContext, $result);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to generate demo data', [
                'user_id' => $user->getKey(),
                'team_id' => $team->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Initialize all seeders
     */
    private function initializeSeeders(): void
    {
        foreach ($this->entitySeederSequence as $seederClass) {
            $this->seeders[$seederClass] = app($seederClass)->initialize();
        }
    }
}
