<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\People as PeopleCustomField;
use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

class PeopleSeeder extends BaseModelSeeder
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
     * @param Team $team The team to create data for
     * @param User $user The user creating the data
     * @param array<string, mixed> $context Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function seedModel(Team $team, User $user, array $context = []): array
    {
        $companies = $context['companies'] ?? [];
        
        if (empty($companies)) {
            return ['people' => []];
        }
        
        $janePerson = $this->createPerson(
            $companies['acme'], 
            $user, 
            'Jane Doe', 
            [
                PeopleCustomField::EMAILS->value => ['jane@example.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-123-4567',
                PeopleCustomField::JOB_TITLE->value => 'Marketing Director',
                PeopleCustomField::LINKEDIN->value => 'https://linkedin.com/in/jane-doe'
            ]
        );
        
        $johnPerson = $this->createPerson(
            $companies['acme'], 
            $user, 
            'John Smith', 
            [
                PeopleCustomField::EMAILS->value => ['john@example.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-987-6543',
                PeopleCustomField::JOB_TITLE->value => 'Chief Financial Officer',
                PeopleCustomField::LINKEDIN->value => 'https://linkedin.com/in/john-smith'
            ]
        );
        
        $sarahPerson = $this->createPerson(
            $companies['technova'], 
            $user, 
            'Sarah Johnson', 
            [
                PeopleCustomField::EMAILS->value => ['sarah@technova.example.com'],
                PeopleCustomField::PHONE_NUMBER->value => '555-456-7890',
                PeopleCustomField::JOB_TITLE->value => 'Chief Technology Officer',
                PeopleCustomField::LINKEDIN->value => 'https://linkedin.com/in/sarah-johnson'
            ]
        );
        
        return [
            'people' => [
                'jane' => $janePerson,
                'john' => $johnPerson,
                'sarah' => $sarahPerson
            ]
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