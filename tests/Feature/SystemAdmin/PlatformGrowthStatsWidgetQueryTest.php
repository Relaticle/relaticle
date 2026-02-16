<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Relaticle\SystemAdmin\Filament\Widgets\PlatformGrowthStatsWidget;
use Relaticle\SystemAdmin\Models\SystemAdministrator;
use Tests\Helpers\QueryCounter;

beforeEach(function () {
    $this->admin = SystemAdministrator::factory()->create();
    $this->actingAs($this->admin, 'sysadmin');
    Filament::setCurrentPanel('sysadmin');
});

it('tracks query count for PlatformGrowthStatsWidget', function () {
    $counter = new QueryCounter;
    $counter->start();

    livewire(PlatformGrowthStatsWidget::class, ['pageFilters' => ['period' => '30']])
        ->assertOk();

    $counter->stop();

    $counter->dump();

    $totalQueries = $counter->count();

    $companiesCount = $counter->findRepeated('from "companies" where "creation_source"');
    $peopleCount = $counter->findRepeated('from "people" where "creation_source"');

    dump("Companies count queries: {$companiesCount['count']}");
    dump("People count queries: {$peopleCount['count']}");
    dump("Total queries: {$totalQueries}");

    expect($totalQueries)->toBeGreaterThan(0);
    expect($totalQueries)->toBeLessThan(15);
});
