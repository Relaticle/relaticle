<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\Opportunity as OpportunityCustomField;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

final class OpportunitySeeder extends BaseModelSeeder
{
    protected string $modelClass = Opportunity::class;

    protected array $fieldCodes = [
        OpportunityCustomField::AMOUNT->value,
        OpportunityCustomField::CLOSE_DATE->value,
        OpportunityCustomField::STAGE->value,
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
            return ['opportunities' => []];
        }

        $opportunity1 = $this->createOpportunity(
            $team,
            $user,
            'Figma Enterprise Plan',
            $companies['figma'],
            [
                OpportunityCustomField::AMOUNT->value => 15000,
                OpportunityCustomField::CLOSE_DATE->value => now()->addWeeks(2)->format('Y-m-d'),
                OpportunityCustomField::STAGE->value => $this->getOptionId(
                    OpportunityCustomField::STAGE->value,
                    'Proposal/Price Quote'
                ),
            ]
        );

        $opportunity2 = $this->createOpportunity(
            $team,
            $user,
            'Apple Developer Partnership',
            $companies['apple'],
            [
                OpportunityCustomField::AMOUNT->value => 25000,
                OpportunityCustomField::CLOSE_DATE->value => now()->addMonths(1)->format('Y-m-d'),
                OpportunityCustomField::STAGE->value => $this->getOptionId(
                    OpportunityCustomField::STAGE->value,
                    'Needs Analysis'
                ),
            ]
        );

        $opportunity3 = $this->createOpportunity(
            $team,
            $user,
            'Airbnb Host Analytics Platform',
            $companies['airbnb'],
            [
                OpportunityCustomField::AMOUNT->value => 20000,
                OpportunityCustomField::CLOSE_DATE->value => now()->addWeeks(4)->format('Y-m-d'),
                OpportunityCustomField::STAGE->value => $this->getOptionId(
                    OpportunityCustomField::STAGE->value,
                    'Initial Contact'
                ),
            ]
        );

        $opportunity4 = $this->createOpportunity(
            $team,
            $user,
            'Notion API Integration',
            $companies['notion'],
            [
                OpportunityCustomField::AMOUNT->value => 18000,
                OpportunityCustomField::CLOSE_DATE->value => now()->addWeeks(3)->format('Y-m-d'),
                OpportunityCustomField::STAGE->value => $this->getOptionId(
                    OpportunityCustomField::STAGE->value,
                    'Discovery/Qualification'
                ),
            ]
        );

        return [
            'opportunities' => [
                'figma_enterprise' => $opportunity1,
                'apple_partnership' => $opportunity2,
                'airbnb_analytics' => $opportunity3,
                'notion_integration' => $opportunity4,
            ],
        ];
    }

    private function createOpportunity(
        Team $team,
        User $user,
        string $name,
        Company $company,
        array $customData
    ): Opportunity {
        $opportunity = $team->opportunities()->create([
            'name' => $name,
            'company_id' => $company->id,
            'creator_id' => $user->id,
            ...$this->getGlobalAttributes(),
        ]);

        $this->applyCustomFields($opportunity, $customData);

        return $opportunity;
    }
}
