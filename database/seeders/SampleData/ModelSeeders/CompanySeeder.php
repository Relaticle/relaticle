<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\Company as CompanyCustomField;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

class CompanySeeder extends BaseModelSeeder
{
    protected string $modelClass = Company::class;
    
    protected array $fieldCodes = [
        CompanyCustomField::DOMAIN_NAME->value,
        CompanyCustomField::ICP->value,
        CompanyCustomField::LINKEDIN->value,
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
        $acmeCompany = $this->createCompany($team, $user, 'Acme Corp', [
            CompanyCustomField::DOMAIN_NAME->value => 'acme.example.com',
            CompanyCustomField::ICP->value => true,
            CompanyCustomField::LINKEDIN->value => 'https://linkedin.com/company/acme-corp'
        ]);
        
        $techCompany = $this->createCompany($team, $user, 'TechNova Solutions', [
            CompanyCustomField::DOMAIN_NAME->value => 'technova.example.com',
            CompanyCustomField::ICP->value => true,
            CompanyCustomField::LINKEDIN->value => 'https://linkedin.com/company/technova-solutions'
        ]);
        
        return [
            'companies' => [
                'acme' => $acmeCompany,
                'technova' => $techCompany
            ]
        ];
    }
    
    private function createCompany(Team $team, User $user, string $name, array $customData): Company
    {
        $company = $team->companies()->create([
            'name' => $name,
            'creator_id' => $user->id,
        ]);
        
        $this->applyCustomFields($company, $customData);
        
        return $company;
    }
} 