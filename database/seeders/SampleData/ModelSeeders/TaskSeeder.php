<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\Task as TaskCustomField;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

class TaskSeeder extends BaseModelSeeder
{
    protected string $modelClass = Task::class;
    
    protected array $fieldCodes = [
        TaskCustomField::DESCRIPTION->value,
        TaskCustomField::DUE_DATE->value,
        TaskCustomField::STATUS->value,
        TaskCustomField::PRIORITY->value,
    ];
    
    /**
     * Seed model implementation
     * 
     * @param Team $team The team to create data for
     * @param User $user The user creating the data
     * @param array<string, mixed> $context Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function seedModel(Team $team, User $user, array $context = []): array
    {
        $task1 = $this->createTask(
            $team,
            $user,
            'Follow up with Jane',
            [
                TaskCustomField::DESCRIPTION->value => 'Discuss the new service contract proposal and address any questions she may have.',
                TaskCustomField::DUE_DATE->value => now()->addDays(3)->format('Y-m-d H:i:s'),
                TaskCustomField::STATUS->value => $this->getOptionId(
                    TaskCustomField::STATUS->value,
                    'To do'
                ),
                TaskCustomField::PRIORITY->value => $this->getOptionId(
                    TaskCustomField::PRIORITY->value,
                    'High'
                )
            ]
        );
        
        $task2 = $this->createTask(
            $team,
            $user,
            'Send contract to John',
            [
                TaskCustomField::DESCRIPTION->value => 'Prepare and send the final contract for review.',
                TaskCustomField::DUE_DATE->value => now()->addDays(5)->format('Y-m-d H:i:s'),
                TaskCustomField::STATUS->value => $this->getOptionId(
                    TaskCustomField::STATUS->value,
                    'To do'
                ),
                TaskCustomField::PRIORITY->value => $this->getOptionId(
                    TaskCustomField::PRIORITY->value,
                    'Medium'
                )
            ]
        );
        
        $task3 = $this->createTask(
            $team,
            $user,
            'Technical discovery call with Sarah',
            [
                TaskCustomField::DESCRIPTION->value => 'Schedule a call to discuss technical requirements for the integration project.',
                TaskCustomField::DUE_DATE->value => now()->addDays(7)->format('Y-m-d H:i:s'),
                TaskCustomField::STATUS->value => $this->getOptionId(
                    TaskCustomField::STATUS->value,
                    'To do'
                ),
                TaskCustomField::PRIORITY->value => $this->getOptionId(
                    TaskCustomField::PRIORITY->value,
                    'High'
                )
            ]
        );
        
        return [
            'tasks' => [
                'jane_followup' => $task1,
                'john_contract' => $task2,
                'sarah_call' => $task3
            ]
        ];
    }
    
    /**
     * Associate tasks with people
     */
    public function associateWithPeople(array $tasks, array $people): void
    {
        if (empty($tasks) || empty($people)) {
            return;
        }
        
        $tasks['jane_followup']->people()->attach($people['jane']->id);
        $tasks['john_contract']->people()->attach($people['john']->id);
        $tasks['sarah_call']->people()->attach($people['sarah']->id);
    }
    
    private function createTask(Team $team, User $user, string $title, array $customData): Task
    {
        $task = $team->tasks()->create([
            'title' => $title,
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);
        
        $this->applyCustomFields($task, $customData);
        
        return $task;
    }
} 