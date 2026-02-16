<?php

declare(strict_types=1);

use App\Enums\CustomFields\OpportunityField;
use App\Filament\Pages\OpportunitiesBoard;
use App\Models\CustomField;
use App\Models\Opportunity;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamCreated;
use Tests\Helpers\QueryCounter;

beforeEach(function () {
    Event::fake()->except([TeamCreated::class]);

    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
    $this->actingAs($this->user);
    Filament::setTenant($this->team);
});

it('renders OpportunitiesBoard with efficient query count', function () {
    $stageField = CustomField::query()
        ->forEntity(Opportunity::class)
        ->where('code', OpportunityField::STAGE->value)
        ->first();

    expect($stageField)->not->toBeNull('Stage custom field should exist after team creation');

    foreach ($stageField->options->take(3) as $option) {
        $opportunity = Opportunity::factory()->create(['team_id' => $this->team->id]);
        $opportunity->saveCustomFieldValue($stageField, $option->getKey());
    }

    $counter = new QueryCounter;
    $counter->start();

    livewire(OpportunitiesBoard::class)
        ->assertOk();

    $counter->stop();

    $stageFieldQueries = $counter->findRepeated('"code" =');
    $perColumnQueries = $counter->findRepeated('left join "custom_field_values" as "cfv"');

    expect($stageFieldQueries['count'])->toBeLessThanOrEqual(2, 'stageCustomField() should be memoized (1 from canAccess + 1 from board render)');
    expect($perColumnQueries['count'])->toBeLessThanOrEqual(2, 'Board records should be fetched in a single batch query, not per-column');
});
