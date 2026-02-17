<?php

declare(strict_types=1);

use App\Enums\CustomFields\OpportunityField;
use App\Filament\Pages\OpportunitiesBoard;
use App\Listeners\CreateTeamCustomFields;
use App\Models\CustomField;
use App\Models\Opportunity;
use App\Models\User;
use Filament\Facades\Filament;
use Laravel\Jetstream\Events\TeamCreated;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);

    $this->team = $this->user->personalTeam();
    app(CreateTeamCustomFields::class)->handle(new TeamCreated($this->team));

    Filament::setTenant($this->team);

    $this->stageField = CustomField::query()
        ->forEntity(Opportunity::class)
        ->where('code', OpportunityField::STAGE)
        ->first();
});

function getBoardRecords(): array
{
    $component = livewire(OpportunitiesBoard::class);
    $board = $component->instance()->getBoard();

    return $board->getBatchedBoardRecords();
}

it('can render the board page', function (): void {
    livewire(OpportunitiesBoard::class)
        ->assertOk();
});

it('displays opportunities in the correct board columns', function (): void {
    $prospecting = $this->stageField->options->firstWhere('name', 'Prospecting');
    $closedWon = $this->stageField->options->firstWhere('name', 'Closed Won');

    $prospectingOpportunity = Opportunity::factory()->for($this->team)->create();
    $prospectingOpportunity->saveCustomFieldValue($this->stageField, $prospecting->getKey());

    $closedWonOpportunity = Opportunity::factory()->for($this->team)->create();
    $closedWonOpportunity->saveCustomFieldValue($this->stageField, $closedWon->getKey());

    $records = getBoardRecords();

    expect($records[(string) $prospecting->getKey()]->pluck('id'))
        ->toContain($prospectingOpportunity->id)
        ->and($records[(string) $closedWon->getKey()]->pluck('id'))
        ->toContain($closedWonOpportunity->id);
});

it('does not show opportunities from other teams', function (): void {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherOpportunity = Opportunity::factory()->for($otherUser->personalTeam())->create();

    $records = getBoardRecords();
    $allRecordIds = collect($records)->flatten()->pluck('id');

    expect($allRecordIds)->not->toContain($otherOpportunity->id);
});

it('moves a card between columns via moveCard', function (): void {
    $prospecting = $this->stageField->options->firstWhere('name', 'Prospecting');
    $qualification = $this->stageField->options->firstWhere('name', 'Qualification');

    $opportunity = Opportunity::factory()->for($this->team)->create();
    $opportunity->saveCustomFieldValue($this->stageField, $prospecting->getKey());

    livewire(OpportunitiesBoard::class)
        ->call('moveCard', (string) $opportunity->id, (string) $qualification->getKey())
        ->assertDispatched('kanban-card-moved');

    $updatedValue = $opportunity->fresh()->customFieldValues()
        ->where('custom_field_id', $this->stageField->getKey())
        ->value($this->stageField->getValueColumn());

    expect($updatedValue)->toBe($qualification->getKey());
});
