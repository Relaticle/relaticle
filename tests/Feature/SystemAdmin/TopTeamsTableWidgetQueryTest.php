<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use Filament\Facades\Filament;
use Relaticle\SystemAdmin\Filament\Widgets\TopTeamsTableWidget;
use Relaticle\SystemAdmin\Models\SystemAdministrator;
use Tests\Helpers\QueryCounter;

beforeEach(function () {
    $this->admin = SystemAdministrator::factory()->create();
    $this->actingAs($this->admin, 'sysadmin');
    Filament::setCurrentPanel('sysadmin');
});

it('renders with efficient query count', function () {
    $team = Team::factory()->create(['personal_team' => false]);
    Company::factory(3)->create(['team_id' => $team->id]);

    $counter = new QueryCounter;
    $counter->start();

    livewire(TopTeamsTableWidget::class, ['pageFilters' => ['period' => '30']])
        ->assertOk();

    $counter->stop();

    expect($counter->count())->toBeLessThanOrEqual(5);
})->skip(
    fn () => config('database.default') === 'sqlite',
    'TopTeamsTableWidget uses PostgreSQL-specific SQL (GREATEST, TIMESTAMP)'
);
