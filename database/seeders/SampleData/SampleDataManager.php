<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData;

use App\Models\User;
use Database\Seeders\SampleData\Contracts\ModelSeederInterface;
use Database\Seeders\SampleData\ModelSeeders\CompanySeeder;
use Database\Seeders\SampleData\ModelSeeders\NoteSeeder;
use Database\Seeders\SampleData\ModelSeeders\OpportunitySeeder;
use Database\Seeders\SampleData\ModelSeeders\PeopleSeeder;
use Database\Seeders\SampleData\ModelSeeders\TaskSeeder;
use Illuminate\Support\Facades\Log;

class SampleDataManager
{
    /**
     * The ordered sequence of model seeders to run
     *
     * @var array<class-string<ModelSeederInterface>>
     */
    protected array $seederSequence = [
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
    protected array $seeders = [];

    /**
     * Generate sample data for a user
     *
     * @param User $user The user to create sample data for
     * @return bool Whether the seeding was successful
     */
    public function generateFor(User $user): bool
    {
        $team = $user->currentTeam;

        if (!$team) {
            return false;
        }

        try {
            $this->initializeSeeders();

            $context = [];

            // Run seeders in sequence
            foreach ($this->seeders as $seeder) {
                $result = $seeder->seed($team, $user, $context);

                // Merge new context data
                $context = array_merge($context, $result);
            }

            // Create relationships after all entities are created
            if (isset($context['tasks'], $context['people'])) {
                $this->createTaskPeopleRelationships($context['tasks'], $context['people']);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to generate sample data', [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Initialize all seeders
     */
    protected function initializeSeeders(): void
    {
        foreach ($this->seederSequence as $seederClass) {
            $this->seeders[$seederClass] = app($seederClass)->initialize();
        }
    }

    /**
     * Create relationships between tasks and people
     *
     * @param array<string, mixed> $tasks
     * @param array<string, mixed> $people
     */
    protected function createTaskPeopleRelationships(array $tasks, array $people): void
    {
        if (empty($tasks) || empty($people)) {
            return;
        }

        $taskRelations = [
            'jane_followup' => 'jane',
            'john_contract' => 'john',
            'sarah_call' => 'sarah',
        ];

        foreach ($taskRelations as $taskKey => $personKey) {
            if (isset($tasks[$taskKey], $people[$personKey])) {
                $tasks[$taskKey]->people()->attach($people[$personKey]->id);
            }
        }
    }
}
