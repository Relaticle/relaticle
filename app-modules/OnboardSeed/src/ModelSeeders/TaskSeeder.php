<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\ModelSeeders;

use Exception;
use Illuminate\Support\Carbon;
use App\Enums\CustomFields\Task as TaskCustomField;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Relaticle\OnboardSeed\Support\BaseModelSeeder;
use Relaticle\OnboardSeed\Support\FixtureRegistry;

final class TaskSeeder extends BaseModelSeeder
{
    protected string $modelClass = Task::class;

    protected string $entityType = 'tasks';

    protected array $fieldCodes = [
        TaskCustomField::DESCRIPTION->value,
        TaskCustomField::DUE_DATE->value,
        TaskCustomField::STATUS->value,
        TaskCustomField::PRIORITY->value,
    ];

    /**
     * Create task entities from fixtures
     *
     * @param  Team  $team  The team to create data for
     * @param  User  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function createEntitiesFromFixtures(Team $team, Authenticatable $user, array $context = []): array
    {
        $fixtures = $this->loadEntityFixtures();
        $tasks = [];

        foreach ($fixtures as $key => $data) {
            $task = $this->createTaskFromFixture($team, $user, $key, $data);

            // Process people assignments if defined in the fixture
            if (isset($data['assigned_people']) && is_array($data['assigned_people'])) {
                $this->assignPeopleToTask($task, $data['assigned_people']);
            }

            $tasks[$key] = $task;
        }

        return [
            'tasks' => $tasks,
        ];
    }

    /**
     * Assign people to a task based on people keys
     *
     * @param  array<int, string>  $peopleKeys
     */
    private function assignPeopleToTask(Task $task, array $peopleKeys): void
    {
        foreach ($peopleKeys as $personKey) {
            $person = FixtureRegistry::get('people', $personKey);

            if (! $person instanceof Model) {
                Log::warning("Person not found for task assignment: {$personKey}");

                continue;
            }

            try {
                $task->people()->attach($person->getKey());
            } catch (Exception $e) {
                report($e);
            }
        }
    }

    /**
     * Create a task from fixture data
     *
     * @param  array<string, mixed>  $data
     */
    private function createTaskFromFixture(
        Team $team,
        Authenticatable $user,
        string $key,
        array $data
    ): Task {
        $attributes = [
            'title' => $data['title'],
            'team_id' => $team->id,
        ];

        $customFields = $data['custom_fields'] ?? [];

        // Define field mappings for custom processing
        $fieldMappings = [
            TaskCustomField::DUE_DATE->value => fn ($value) => is_string($value)
                ? $this->formatDate($this->evaluateTemplateExpression($value))
                : $value,
            TaskCustomField::STATUS->value => 'option',
            TaskCustomField::PRIORITY->value => 'option',
        ];

        // Process custom fields using utility method
        $processedFields = $this->processCustomFieldValues($customFields, $fieldMappings);

        /** @var Task */
        return $this->registerEntityFromFixture($key, $attributes, $processedFields, $team, $user);
    }

    /**
     * Format a date value for the task due date
     *
     * @param  mixed  $dateValue  The date value returned from template expression
     * @return string The formatted date string
     */
    private function formatDate(mixed $dateValue): string
    {
        if ($dateValue instanceof Carbon) {
            return $dateValue->format('Y-m-d H:i:s');
        }

        return (string) $dateValue;
    }
}
