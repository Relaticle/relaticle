<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\ModelSeeders;

use App\Enums\CustomFields\PeopleField as PeopleCustomField;
use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Relaticle\OnboardSeed\Support\BaseModelSeeder;
use Relaticle\OnboardSeed\Support\FixtureRegistry;

final class PeopleSeeder extends BaseModelSeeder
{
    protected string $modelClass = People::class;

    protected string $entityType = 'people';

    protected array $fieldCodes = [
        PeopleCustomField::EMAILS->value,
        PeopleCustomField::PHONE_NUMBER->value,
        PeopleCustomField::JOB_TITLE->value,
        PeopleCustomField::LINKEDIN->value,
    ];

    /**
     * Create people entities from fixtures
     *
     * @param  Team  $team  The team to create data for
     * @param  User  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function createEntitiesFromFixtures(Team $team, Authenticatable $user, array $context = []): array
    {
        $fixtures = $this->loadEntityFixtures();
        $people = [];

        foreach ($fixtures as $key => $data) {
            $companyKey = $data['company'] ?? null;

            if (! $companyKey) {
                Log::warning("Missing company reference for person: {$key}");

                continue;
            }

            $company = FixtureRegistry::get('companies', $companyKey);

            if (! $company instanceof Company) {
                Log::warning("Company not found for person: {$key}, company key: {$companyKey}");

                continue;
            }

            $person = $this->createPersonFromFixture($company, $user, $key, $data);
            $people[$key] = $person;
        }

        return [
            'people' => $people,
        ];
    }

    /**
     * Create a person from fixture data
     *
     * @param  array<string, mixed>  $data
     */
    private function createPersonFromFixture(Company $company, Authenticatable $user, string $key, array $data): People
    {
        $attributes = [
            'name' => $data['name'],
            'company_id' => $company->id,
            'team_id' => $company->team_id,
        ];

        $customFields = $data['custom_fields'] ?? [];

        /** @var Team $team */
        $team = $company->team;

        /** @var People */
        return $this->registerEntityFromFixture($key, $attributes, $customFields, $team, $user);
    }
}
