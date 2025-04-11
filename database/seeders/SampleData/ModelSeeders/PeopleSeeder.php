<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\People as PeopleCustomField;
use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

final class PeopleSeeder extends BaseModelSeeder
{
    protected string $modelClass = People::class;

    protected array $fieldCodes = [
        PeopleCustomField::EMAILS->value,
        PeopleCustomField::PHONE_NUMBER->value,
        PeopleCustomField::JOB_TITLE->value,
        PeopleCustomField::LINKEDIN->value,
    ];

    /**
     * Seed model implementation
     *
     * @param  Team  $team  The team to create data for
     * @param  User  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function seedModel(Team $team, User $user, array $context = []): array
    {
        $companies = $context['companies'] ?? [];

        if (empty($companies)) {
            return ['people' => []];
        }

        $dylanPerson = $this->createPerson(
            $companies['figma'],
            $user,
            'Dylan Field',
            [
                PeopleCustomField::EMAILS->value => ['dylan@figma.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-123-4567',
                PeopleCustomField::JOB_TITLE->value => 'CEO',
                PeopleCustomField::LINKEDIN->value => 'https://www.linkedin.com/in/dylanfield/',
            ]
        );

        $timPerson = $this->createPerson(
            $companies['apple'],
            $user,
            'Tim Cook',
            [
                PeopleCustomField::EMAILS->value => ['tim@apple.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-987-6543',
                PeopleCustomField::JOB_TITLE->value => 'CEO',
                PeopleCustomField::LINKEDIN->value => 'https://www.linkedin.com/in/timcook/',
            ]
        );

        $brianPerson = $this->createPerson(
            $companies['airbnb'],
            $user,
            'Brian Chesky',
            [
                PeopleCustomField::EMAILS->value => ['brian@airbnb.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-456-7890',
                PeopleCustomField::JOB_TITLE->value => 'CEO & Co-founder',
                PeopleCustomField::LINKEDIN->value => 'https://www.linkedin.com/in/brianchesky/',
            ]
        );

        $ivanPerson = $this->createPerson(
            $companies['notion'],
            $user,
            'Ivan Zhao',
            [
                PeopleCustomField::EMAILS->value => ['ivan@notion.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-789-0123',
                PeopleCustomField::JOB_TITLE->value => 'Co-founder',
                PeopleCustomField::LINKEDIN->value => 'https://www.linkedin.com/in/ivzhao/',
            ]
        );

        return [
            'people' => [
                'dylan' => $dylanPerson,
                'tim' => $timPerson,
                'brian' => $brianPerson,
                'ivan' => $ivanPerson,
            ],
        ];
    }

    private function createPerson(Company $company, User $user, string $name, array $customData): People
    {
        $person = $company->people()->create([
            'name' => $name,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'team_id' => $company->team_id,
        ]);

        $this->applyCustomFields($person, $customData);

        return $person;
    }
}
