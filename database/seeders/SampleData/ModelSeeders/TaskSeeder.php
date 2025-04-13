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
            'Follow up with Dylan',
            [
                TaskCustomField::DESCRIPTION->value => 'Discuss the Figma enterprise plan proposal and address any questions he may have.',
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
            'Send proposal to Tim',
            [
                TaskCustomField::DESCRIPTION->value => 'Prepare and send the final proposal for the Apple developer partnership.',
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
            'Discovery call with Brian',
            [
                TaskCustomField::DESCRIPTION->value => 'Schedule a call to discuss potential partnership opportunities with Airbnb.',
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

        $task4 = $this->createTask(
            $team,
            $user,
            'Integration meeting with Ivan',
            [
                TaskCustomField::DESCRIPTION->value => 'Discuss API integration possibilities with Notion and explore collaboration features.',
                TaskCustomField::DUE_DATE->value => now()->addDays(10)->format('Y-m-d H:i:s'),
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
                'dylan_followup' => $task1,
                'tim_proposal' => $task2,
                'brian_call' => $task3,
                'ivan_meeting' => $task4
            ]
        ];
    }

    /**
     * Associate tasks with people
     */
    public function associateWithPeople(array $tasks, array $people): void
    {
        if ($tasks === [] || $people === []) {
            return;
        }

        $tasks['dylan_followup']->people()->attach($people['dylan']->id);
        $tasks['tim_proposal']->people()->attach($people['tim']->id);
        $tasks['brian_call']->people()->attach($people['brian']->id);
        $tasks['ivan_meeting']->people()->attach($people['ivan']->id);
    }

    private function createTask(Team $team, User $user, string $title, array $customData): Task
    {
        $task = $team->tasks()->create([
            'title' => $title,
            'creator_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->applyCustomFields($task, $customData);

        return $task;
    }
}
