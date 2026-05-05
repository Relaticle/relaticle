<?php

declare(strict_types=1);

use App\Models\Team;
use Filament\Facades\Filament;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\SystemAdmin\Filament\Resources\AiCreditBalanceResource\Pages\ListAiCreditBalances;
use Relaticle\SystemAdmin\Filament\Resources\AiCreditBalanceResource\Pages\ViewAiCreditBalance;
use Relaticle\SystemAdmin\Models\SystemAdministrator;

beforeEach(function (): void {
    $this->actingAs(SystemAdministrator::factory()->create(), 'sysadmin');
    Filament::setCurrentPanel(Filament::getPanel('sysadmin'));
});

it('lists balances across all tenants', function (): void {
    $team1 = Team::factory()->create(['name' => 'Acme']);
    $team2 = Team::factory()->create(['name' => 'Globex']);
    $b1 = AiCreditBalance::factory()->create(['team_id' => $team1->getKey(), 'credits_remaining' => 200]);
    $b2 = AiCreditBalance::factory()->create(['team_id' => $team2->getKey(), 'credits_remaining' => 50]);

    livewire(ListAiCreditBalances::class)
        ->assertCanSeeTableRecords([$b1, $b2])
        ->assertCanRenderTableColumn('team.name')
        ->assertCanRenderTableColumn('credits_remaining')
        ->assertCanRenderTableColumn('credits_used');
});

it('filters by low balance', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $low = AiCreditBalance::factory()->create(['team_id' => $team1->getKey(), 'credits_remaining' => 5]);
    $high = AiCreditBalance::factory()->create(['team_id' => $team2->getKey(), 'credits_remaining' => 500]);

    livewire(ListAiCreditBalances::class)
        ->filterTable('low_balance')
        ->assertCanSeeTableRecords([$low])
        ->assertCanNotSeeTableRecords([$high]);
});

it('shows the balance detail page', function (): void {
    $balance = AiCreditBalance::factory()->create();

    livewire(ViewAiCreditBalance::class, ['record' => $balance->getKey()])
        ->assertSuccessful();
});
