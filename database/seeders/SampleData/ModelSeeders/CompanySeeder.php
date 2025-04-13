<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\Company as CompanyCustomField;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

final class CompanySeeder extends BaseModelSeeder
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
     * @param  Team  $team  The team to create data for
     * @param  User  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function seedModel(Team $team, User $user, array $context = []): array
    {
        $figmaCompany = $this->createCompany($team, $user, 'Figma', [
            CompanyCustomField::DOMAIN_NAME->value => 'https://figma.com',
            CompanyCustomField::ICP->value => true,
            CompanyCustomField::LINKEDIN->value => 'https://www.linkedin.com/company/figma',
        ]);

        $appleCompany = $this->createCompany($team, $user, 'Apple', [
            CompanyCustomField::DOMAIN_NAME->value => 'https://apple.com',
            CompanyCustomField::ICP->value => true,
            CompanyCustomField::LINKEDIN->value => 'https://www.linkedin.com/company/apple',
        ]);

        $airbnbCompany = $this->createCompany($team, $user, 'Airbnb', [
            CompanyCustomField::DOMAIN_NAME->value => 'https://airbnb.com',
            CompanyCustomField::ICP->value => true,
            CompanyCustomField::LINKEDIN->value => 'https://www.linkedin.com/company/airbnb',
        ]);

        $notionCompany = $this->createCompany($team, $user, 'Notion', [
            CompanyCustomField::DOMAIN_NAME->value => 'https://notion.com',
            CompanyCustomField::ICP->value => true,
            CompanyCustomField::LINKEDIN->value => 'https://www.linkedin.com/company/notion-so',
        ]);

        return [
            'companies' => [
                'figma' => $figmaCompany,
                'apple' => $appleCompany,
                'airbnb' => $airbnbCompany,
                'notion' => $notionCompany,
            ],
        ];
    }

    private function createCompany(Team $team, User $user, string $name, array $customData): Company
    {
        $company = $team->companies()->create([
            'name' => $name,
            'creator_id' => $user->id,
            'account_owner_id' => $user->id,
        ]);

        $this->applyCustomFields($company, $customData);

        return $company;
    }
}
