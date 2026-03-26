<?php

declare(strict_types=1);

use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\OpportunityResource\Pages\ViewOpportunity;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\ActivityLog\Filament\RelationManagers\ActivitiesRelationManager;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('renders the activity relation manager on company view page', function (): void {
    $company = Company::factory()->for($this->team)->create();

    livewire(ActivitiesRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => ViewCompany::class,
    ])->assertOk();
});

it('renders the activity relation manager on people view page', function (): void {
    $person = People::factory()->for($this->team)->create();

    livewire(ActivitiesRelationManager::class, [
        'ownerRecord' => $person,
        'pageClass' => ViewPeople::class,
    ])->assertOk();
});

it('renders the activity relation manager on opportunity view page', function (): void {
    $opportunity = Opportunity::factory()->for($this->team)->create();

    livewire(ActivitiesRelationManager::class, [
        'ownerRecord' => $opportunity,
        'pageClass' => ViewOpportunity::class,
    ])->assertOk();
});
