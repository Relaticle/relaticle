<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\Opportunity as OpportunityCustomField;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;

class OpportunitySeeder extends BaseModelSeeder
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
     * @param Team $team The team to create data for
     * @param User $user The user creating the data
     * @param array<string, mixed> $context Context data from previous seeders
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
            'Acme Corp Service Contract',
            $companies['acme'],
            [
                OpportunityCustomField::AMOUNT->value => 15000,
                OpportunityCustomField::CLOSE_DATE->value => now()->addWeeks(2)->format('Y-m-d'),
                OpportunityCustomField::STAGE->value => $this->getOptionId(
                    OpportunityCustomField::STAGE->value,
                    'Proposal/Price Quote'
                )
            ]
        );
        
        $opportunity2 = $this->createOpportunity(
            $team,
            $user,
            'TechNova Integration Project',
            $companies['technova'],
            [
                OpportunityCustomField::AMOUNT->value => 25000,
                OpportunityCustomField::CLOSE_DATE->value => now()->addMonths(1)->format('Y-m-d'),
                OpportunityCustomField::STAGE->value => $this->getOptionId(
                    OpportunityCustomField::STAGE->value,
                    'Needs Analysis'
                )
            ]
        );
        
        return [
            'opportunities' => [
                'acme_contract' => $opportunity1,
                'technova_project' => $opportunity2
            ]
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
            'user_id' => $user->id,
        ]);
        
        $this->applyCustomFields($opportunity, $customData);
        
        return $opportunity;
    }
} 